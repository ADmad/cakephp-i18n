<?php
declare(strict_types=1);

namespace ADmad\I18n\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Language string extractor
 */
class I18nExtractCommand extends \Cake\Command\I18nExtractCommand
{
    use I18nModelTrait;

    /**
     * Default model for storing translation messages.
     */
    public const DEFAULT_MODEL = 'I18nMessages';

    /**
     * App languages.
     *
     * @var array
     */
    protected $_languages = [];

    /**
     * The name of this command.
     *
     * @var string
     */
    protected $name = 'admad/i18n extract';

    /**
     * Get the command name.
     *
     * Returns the command name based on class name.
     * For e.g. for a command with class name `UpdateTableCommand` the default
     * name returned would be `'update_table'`.
     *
     * @return string
     */
    public static function defaultName(): string
    {
        return 'admad/i18n extract';
    }

    /**
     * Execute the command.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $plugin = '';
        if ($args->getOption('exclude')) {
            $this->_exclude = explode(',', (string)$args->getOption('exclude'));
        }
        if ($args->getOption('files')) {
            $this->_files = explode(',', (string)$args->getOption('files'));
        }
        if ($args->getOption('paths')) {
            $this->_paths = explode(',', (string)$args->getOption('paths'));
        } elseif ($args->getOption('plugin')) {
            $plugin = Inflector::camelize((string)$args->getOption('plugin'));
            $this->_paths = [Plugin::classPath($plugin), Plugin::templatePath($plugin)];
        } else {
            $this->_getPaths($io);
        }

        if ($args->hasOption('extract-core')) {
            $this->_extractCore = !(strtolower((string)$args->getOption('extract-core')) === 'no');
        } else {
            $response = $io->askChoice(
                'Would you like to extract the messages from the CakePHP core?',
                ['y', 'n'],
                'n'
            );
            $this->_extractCore = strtolower($response) === 'y';
        }

        if ($args->hasOption('exclude-plugins') && $this->_isExtractingApp()) {
            $this->_exclude = array_merge($this->_exclude, App::path('plugins'));
        }

        if ($this->_extractCore) {
            $this->_paths[] = CAKE;
        }

        if ($args->hasOption('merge')) {
            $this->_merge = !(strtolower((string)$args->getOption('merge')) === 'no');
        } else {
            $io->out();
            $response = $io->askChoice(
                'Would you like to merge all domain strings into the default.pot file?',
                ['y', 'n'],
                'n'
            );
            $this->_merge = strtolower($response) === 'y';
        }

        $this->_markerError = (bool)$args->getOption('marker-error');
        if (property_exists($this, '_relativePaths')) {
            /** @psalm-suppress UndefinedThisPropertyAssignment */
            $this->_relativePaths = (bool)$args->getOption('relative-paths');
        }

        if (empty($this->_files)) {
            $this->_searchFiles();
        }

        $this->_extract($args, $io);

        return static::CODE_SUCCESS;
    }

    /**
     * Extract text
     *
     * @param \Cake\Console\Arguments $args The Arguments instance
     * @param \Cake\Console\ConsoleIo $io The io instance
     * @return void
     */
    protected function _extract(Arguments $args, ConsoleIo $io): void
    {
        $io->out();
        $io->out();
        $io->out('Extracting...');
        $io->hr();
        $io->out('Paths:');
        foreach ($this->_paths as $path) {
            $io->out('   ' . $path);
        }
        $io->hr();
        $this->_extractTokens($args, $io);

        $this->_getLanguages($args);
        $this->_saveMessages($args, $io);

        $this->_paths = $this->_files = $this->_storage = [];
        $this->_translations = $this->_tokens = [];
        $io->out();
        if ($this->_countMarkerError) {
            $io->err("{$this->_countMarkerError} marker error(s) detected.");
            $io->err(' => Use the --marker-error option to display errors.');
        }

        $io->out('Done.');
    }

    /**
     * Get app languages.
     *
     * @param \Cake\Console\Arguments $args The Arguments instance
     * @return void
     */
    protected function _getLanguages(Arguments $args): void
    {
        $langs = (string)$args->getOption('languages');
        if ($langs) {
            $this->_languages = explode(',', $langs);

            return;
        }

        $langs = Configure::read('I18n.languages');
        if (empty($langs)) {
            return;
        }

        $langs = Hash::normalize($langs);
        foreach ($langs as $key => $value) {
            if (isset($value['locale'])) {
                $this->_languages[] = $value['locale'];
            } else {
                $this->_languages[] = $key;
            }
        }
    }

    /**
     * Save translation messages to repository.
     *
     * @param \Cake\Console\Arguments $args The Arguments instance
     * @param \Cake\Console\ConsoleIo $io The io instance
     * @return void
     */
    protected function _saveMessages(Arguments $args, ConsoleIo $io): void
    {
        $paths = $this->_paths;
        /** @psalm-suppress UndefinedConstant */
        $paths[] = realpath(APP) . DIRECTORY_SEPARATOR;

        usort($paths, function (string $a, string $b): int {
            return strlen($a) - strlen($b);
        });

        $domains = null;
        if ($args->getOption('domains')) {
            $domains = explode(',', (string)$args->getOption('domains'));
        }

        $this->_loadModel($args);

        foreach ($this->_translations as $domain => $translations) {
            if (!empty($domains) && !in_array($domain, $domains)) {
                continue;
            }
            if ($this->_merge) {
                $domain = 'default';
            }
            foreach ($translations as $msgid => $contexts) {
                foreach ($contexts as $context => $details) {
                    $references = null;
                    if (!$args->getOption('no-location')) {
                        $files = $details['references'];
                        $occurrences = [];
                        foreach ($files as $file => $lines) {
                            $lines = array_unique($lines);
                            $occurrences[] = $file . ':' . implode(';', $lines);
                        }
                        $occurrences = implode("\n", $occurrences);
                        $occurrences = str_replace($paths, '', $occurrences);
                        $references = str_replace(DIRECTORY_SEPARATOR, '/', $occurrences);
                    }

                    $this->_save(
                        $domain,
                        $msgid,
                        $details['msgid_plural'] === false ? null : $details['msgid_plural'],
                        $context ?: null,
                        $references
                    );
                }
            }
        }
    }

    /**
     * Save translation record to repository.
     *
     * @param string $domain Domain name
     * @param string $singular Singular message id.
     * @param string|null $plural Plural message id.
     * @param string|null $context Context.
     * @param string|null $refs Source code references.
     * @return void
     */
    protected function _save(
        string $domain,
        string $singular,
        ?string $plural = null,
        ?string $context = null,
        ?string $refs = null
    ): void {
        foreach ($this->_languages as $locale) {
            $found = $this->_model->find()
                ->where(compact('domain', 'locale', 'singular'))
                ->count();

            if (!$found) {
                $entity = $this->_model->newEntity(compact(
                    'domain',
                    'locale',
                    'singular',
                    'plural',
                    'context',
                    'refs'
                ), ['guard' => false]);

                $this->_model->save($entity);
            }
        }
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to configure
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'Extract translated strings from application source files. ' .
            'Source files are parsed and string literal format strings ' .
            'provided to the <info>__</info> family of functions are extracted.'
        )->addOption('model', [
            'help' => 'Model to use for storing messages. Defaults to: ' . static::DEFAULT_MODEL,
        ])->addOption('languages', [
            'help' => 'Comma separated list of languages used by app. Defaults used from `I18n.languages` config.',
        ])->addOption('app', [
            'help' => 'Directory where your application is located.',
        ])->addOption('paths', [
            'help' => 'Comma separated list of paths that are searched for source files.',
        ])->addOption('merge', [
            'help' => 'Merge all domain strings into a single `default` domain.',
            'default' => 'no',
            'choices' => ['yes', 'no'],
        ])->addOption('relative-paths', [
            'help' => 'Use application relative paths in references.',
            'boolean' => true,
            'default' => false,
        ])->addOption('files', [
            'help' => 'Comma separated list of files to parse.',
        ])->addOption('exclude-plugins', [
            'boolean' => true,
            'default' => true,
            'help' => 'Ignores all files in plugins if this command is run inside from the same app directory.',
        ])->addOption('plugin', [
            'help' => 'Extracts tokens only from the plugin specified and '
                . 'puts the result in the plugin\'s Locale directory.',
        ])->addOption('exclude', [
            'help' => 'Comma separated list of directories to exclude.' .
                ' Any path containing a path segment with the provided values will be skipped. E.g. test,vendors',
        ])->addOption('extract-core', [
            'help' => 'Extract messages from the CakePHP core libraries.',
            'choices' => ['yes', 'no'],
        ])->addOption('no-location', [
            'boolean' => true,
            'default' => false,
            'help' => 'Do not write file locations for each extracted message.',
        ])->addOption('marker-error', [
            'boolean' => true,
            'default' => false,
            'help' => 'Do not display marker error.',
        ]);

        return $parser;
    }
}

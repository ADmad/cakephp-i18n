<?php
declare(strict_types=1);

namespace ADmad\I18n\Command;

use Cake\Command\I18nExtractCommand as CakeI18nExtractCommand;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * Language string extractor
 */
class I18nExtractCommand extends CakeI18nExtractCommand
{
    use I18nModelTrait;

    /**
     * Default model for storing translation messages.
     *
     * @var string
     */
    public const string DEFAULT_MODEL = 'I18nMessages';

    /**
     * App languages.
     *
     * @var list<string>
     */
    protected array $languages = [];

    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'admad/i18n extract';

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
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $this->languages = $this->getLanguages();
        if ($this->languages === []) {
            $this->io->err(
                'You must specify the languages list using the `I18n.languages` config or the `--languages` option.',
            );

            return static::CODE_ERROR;
        }
        $plugin = '';
        if ($this->args->hasOption('exclude')) {
            $this->exclude = explode(',', (string)$this->args->getOption('exclude'));
        }
        if ($this->args->hasOption('files')) {
            $this->files = explode(',', (string)$this->args->getOption('files'));
        }
        if ($this->args->hasOption('paths')) {
            $this->paths = explode(',', (string)$this->args->getOption('paths'));
        } elseif ($this->args->hasOption('plugin')) {
            $plugin = Inflector::camelize((string)$this->args->getOption('plugin'));
            $this->paths = [Plugin::classPath($plugin), Plugin::templatePath($plugin)];
        } else {
            $this->getPaths();
        }
        if ($this->args->hasOption('extract-core')) {
            $this->extractCore = !(strtolower((string)$this->args->getOption('extract-core')) === 'no');
        } else {
            $response = $this->io->askChoice(
                'Would you like to extract the messages from the CakePHP core?',
                ['y', 'n'],
                'n',
            );
            $this->extractCore = strtolower($response) === 'y';
        }
        if ($this->args->hasOption('exclude-plugins') && $this->isExtractingApp()) {
            $this->exclude = array_merge($this->exclude, App::path('plugins'));
        }
        if ($this->extractCore) {
            $this->paths[] = CAKE;
        }
        if ($this->args->hasOption('merge')) {
            $this->merge = !(strtolower((string)$this->args->getOption('merge')) === 'no');
        } else {
            $this->io->out();
            $response = $this->io->askChoice(
                'Would you like to merge all domain strings into the default.pot file?',
                ['y', 'n'],
                'n',
            );
            $this->merge = strtolower($response) === 'y';
        }
        $this->markerError = (bool)$this->args->getOption('marker-error');
        if (property_exists($this, '_relativePaths')) {
            /** @psalm-suppress UndefinedThisPropertyAssignment */
            $this->_relativePaths = (bool)$this->args->getOption('relative-paths');
        }
        if (empty($this->files)) {
            $this->searchFiles();
        }
        $this->extract();

        return static::CODE_SUCCESS;
    }

    /**
     * Extract text
     *
     * @return void
     */
    protected function extract(): void
    {
        $this->io->out();
        $this->io->out();
        $this->io->out('Extracting...');
        $this->io->hr();
        $this->io->out('Paths:');
        foreach ($this->paths as $path) {
            $this->io->out('   ' . $path);
        }
        $this->io->hr();
        $this->extractTokens();
        $this->saveMessages();
        $this->paths = $this->files = $this->storage = [];
        $this->translations = $this->tokens = [];
        $this->io->out();
        if ($this->countMarkerError) {
            $this->io->err("{$this->countMarkerError} marker error(s) detected.");
            $this->io->err(' => Use the --marker-error option to display errors.');
        }
        $this->io->out('Done.');
    }

    /**
     * Get app languages.
     *
     * @return list<string>
     */
    protected function getLanguages(): array
    {
        $langs = (string)$this->args->getOption('languages');
        if ($langs) {
            return explode(',', $langs);
        }
        $langs = Configure::read('I18n.languages', []);
        if ($langs === []) {
            return [];
        }
        $return = [];
        $langs = Hash::normalize($langs);
        foreach ($langs as $key => $value) {
            if (isset($value['locale'])) {
                $return[] = $value['locale'];
            } else {
                $return[] = $key;
            }
        }

        return $return;
    }

    /**
     * Save translation messages to repository.
     *
     * @return void
     */
    protected function saveMessages(): void
    {
        $paths = $this->paths;
        /** @psalm-suppress UndefinedConstant, PossiblyFalseOperand */
        $paths[] = realpath(APP) . DIRECTORY_SEPARATOR;
        usort($paths, function (string $a, string $b): int {
            return strlen($a) - strlen($b);
        });
        $domains = [];
        if ($this->args->hasOption('domains')) {
            $domains = explode(',', (string)$this->args->getOption('domains'));
        }
        $this->loadModel();
        foreach ($this->translations as $domain => $translations) {
            if ($domains && !in_array($domain, $domains)) {
                continue;
            }
            if ($this->merge) {
                $domain = 'default';
            }
            foreach ($translations as $msgid => $contexts) {
                foreach ($contexts as $context => $details) {
                    $references = null;
                    if (!(bool)$this->args->getOption('no-location')) {
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

                    $this->save(
                        $domain,
                        $msgid,
                        $details['msgid_plural'] === false ? null : $details['msgid_plural'],
                        $context ?: null,
                        $references,
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
    protected function save(
        string $domain,
        string $singular,
        ?string $plural = null,
        ?string $context = null,
        ?string $refs = null,
    ): void {
        foreach ($this->languages as $locale) {
            if (
                $this->model->exists(compact('domain', 'locale', 'singular'))
            ) {
                continue;
            }

            $entity = $this->model->newEntity(compact(
                'domain',
                'locale',
                'singular',
                'plural',
                'context',
                'refs',
            ), ['guard' => false]);

            $this->model->save($entity);
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
            'provided to the <info>__</info> family of functions are extracted.',
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

<?php
namespace ADmad\I18n\Shell\Task;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Shell\Task\ExtractTask as CoreExtractTask;
use Cake\Utility\Hash;

/**
 * Extract shell task.
 */
class ExtractTask extends CoreExtractTask
{
    /**
     * App locales.
     *
     * @var array
     */
    protected $_locales = [];

    /**
     * Model instance to save translation messages to.
     *
     * @var \Cake\ORM\Table|null
     */
    protected $_model = null;

    /**
     * Gets the option parser instance and configures it.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->description(
            'CakePHP Language String Extraction:'
        )->addOption('app', [
            'help' => 'Directory where your application is located.',
        ])->addOption('paths', [
            'help' => 'Comma separated list of paths.',
        ])->addOption('merge', [
            'help' => 'Merge all domain strings into the "default" domain.',
            'choices' => ['yes', 'no'],
        ])->addOption('model', [
            'help' => 'Model to use for storing messages.',
        ])->addOption('locales', [
            'help' => 'Comma separated list of locales used by app.',
        ])->addOption('files', [
            'help' => 'Comma separated list of files.',
        ])->addOption('exclude-plugins', [
            'boolean' => true,
            'default' => true,
            'help' => 'Ignores all files in plugins if this command is run inside from the same app directory.',
        ])->addOption('plugin', [
            'help' => 'Extracts tokens only from the plugin specified and puts the result in the plugin\'s Locale directory.',
        ])->addOption('ignore-model-validation', [
            'boolean' => true,
            'default' => false,
            'help' => 'Ignores validation messages in the $validate property.' .
                ' If this flag is not set and the command is run from the same app directory,' .
                ' all messages in model validation rules will be extracted as tokens.',
        ])->addOption('validation-domain', [
            'help' => 'If set to a value, the localization domain to be used for model validation messages.',
        ])->addOption('exclude', [
            'help' => 'Comma separated list of directories to exclude.' .
                ' Any path containing a path segment with the provided values will be skipped. E.g. test,vendors',
        ])->addOption('extract-core', [
            'help' => 'Extract messages from the CakePHP core libs.',
            'choices' => ['yes', 'no'],
        ])->addOption('no-location', [
            'boolean' => true,
            'default' => false,
            'help' => 'Do not write file locations for each extracted message.',
        ]);

        return $parser;
    }

    /**
     * Extract text.
     *
     * @return void
     */
    protected function _extract()
    {
        $this->out();
        $this->out();
        $this->out('Extracting...');
        $this->hr();
        $this->out('Paths:');
        foreach ($this->_paths as $path) {
            $this->out('   ' . $path);
        }
        $this->out('Output Directory: ' . $this->_output);
        $this->hr();
        $this->_extractTokens();

        $this->_locales();
        $this->_write();

        $this->_paths = $this->_files = $this->_storage = [];
        $this->_translations = $this->_tokens = [];
        $this->out();
        $this->out('Done.');
    }

    /**
     * Write translations to database.
     *
     * @return void
     */
    protected function _write()
    {
        $paths = $this->_paths;
        $paths[] = realpath(APP) . DIRECTORY_SEPARATOR;

        usort($paths, function ($a, $b) {
            return strlen($a) - strlen($b);
        });

        $domains = null;
        if (!empty($this->params['domains'])) {
            $domains = explode(',', $this->params['domains']);
        }

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
                    if (!$this->param('no-location')) {
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
     * Save translation record to database.
     *
     * @param string $domain Domain name
     * @param string $singular Singular message id.
     * @param string|null $plural Plural message id.
     * @param string|null $context Context.
     * @param string|null $refs Source code references.
     *
     * @return void
     */
    protected function _save($domain, $singular, $plural = null, $context = null, $refs = null)
    {
        $model = $this->_model();

        foreach ($this->_locales as $locale) {
            $found = $model->find()
                ->where(compact('domain', 'locale', 'singular'))
                ->count();

            if (!$found) {
                $entity = $model->newEntity(compact(
                    'domain',
                    'locale',
                    'singular',
                    'plural',
                    'context',
                    'refs'
                ), ['guard' => false]);

                $model->save($entity);
            }
        }
    }

    /**
     * Get translation model.
     *
     * @return \Cake\ORM\Table
     */
    protected function _model()
    {
        if ($this->_model !== null) {
            return $this->_model;
        }

        $model = 'I18nMessages';
        if (!empty($this->params['model'])) {
            $model = $this->params['model'];
        }

        return $this->_model = TableRegistry::get($model);
    }

    /**
     * Get app locales.
     *
     * @return void
     */
    protected function _locales()
    {
        if (!empty($this->params['locales'])) {
            $this->_locales = explode(',', $this->params['locales']);

            return;
        }

        $langs = Configure::read('I18n.languages');
        if (empty($langs)) {
            return;
        }

        $this->_locales = [];
        $langs = Hash::normalize($langs);
        foreach ($langs as $key => $value) {
            if (isset($value['locale'])) {
                $this->_locales[] = $value['locale'];
            } else {
                $this->_locales[] = $key;
            }
        }
    }
}

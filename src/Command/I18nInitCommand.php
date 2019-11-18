<?php
declare(strict_types=1);

namespace ADmad\I18n\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Command for interactive I18N management.
 */
class I18nInitCommand extends \Cake\Command\I18nInitCommand
{
    use I18nModelTrait;

    /**
     * Default model for storing translation messages.
     */
    public const DEFAULT_MODEL = 'I18nMessages';

    /**
     * The name of this command.
     *
     * @var string
     */
    protected $name = 'i18n init';

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'i18n init';
    }

    /**
     * Execute the command
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $language = $args->getArgument('language');
        if (!$language) {
            $language = $io->ask('Please specify language code, e.g. `en`, `eng`, `en_US` etc.');
        }
        if (strlen($language) < 2) {
            $io->err('Invalid language code. Valid is `en`, `eng`, `en_US` etc.');

            return static::CODE_ERROR;
        }

        $fields = ['domain', 'singular', 'plural', 'context'];

        $model = $this->_loadModel($args);
        $messages = $model->find()
            ->select($fields)
            ->distinct(['domain', 'singular', 'context'])
            ->disableHydration()
            ->toArray();

        $entities = $model->newEntities($messages);

        $return = $model->getConnection()->transactional(
            function () use ($model, $entities, $language) {
                $model->deleteAll([
                    'locale' => $language,
                ]);

                foreach ($entities as $entity) {
                    $entity->set('locale', $language);
                    if ($model->save($entity) === false) {
                        return false;
                    }
                }
            }
        );

        if ($return) {
            $io->out('Created ' . count($messages) . ' messages for "' . $language . '"');
        } else {
            $io->out('Unable to create messages for "' . $language . '"');
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Initialize new language')
           ->addArgument('language', [
               'help' => 'Language code, e.g. `en`, `eng`, `en_US`.',
           ])
            ->addOption('model', [
                'help' => 'Model to use for storing messages. Defaults to: ' . static::DEFAULT_MODEL,
            ]);

        return $parser;
    }
}

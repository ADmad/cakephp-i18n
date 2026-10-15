<?php
declare(strict_types=1);

namespace ADmad\I18n\Command;

use Cake\Command\I18nInitCommand as CakeI18nInitCommand;
use Cake\Console\ConsoleOptionParser;

/**
 * Command for interactive I18N management.
 */
class I18nInitCommand extends CakeI18nInitCommand
{
    use I18nModelTrait;

    /**
     * Default model for storing translation messages.
     */
    public const string DEFAULT_MODEL = 'I18nMessages';

    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'admad/i18n init';

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'admad/i18n init';
    }

    /**
     * Execute the command
     *
     * @return int|null The exit code or null for success
     */
    public function execute(): ?int
    {
        $language = $this->args->getArgument('language');
        if (!$language) {
            $language = $this->io->ask('Please specify language code, e.g. `en`, `eng`, `en_US` etc.');
        }

        if (strlen($language) < 2) {
            $this->io->err('Invalid language code. Valid are `en`, `eng`, `en_US` etc.');

            return static::CODE_ERROR;
        }

        $model = $this->loadModel();
        $messages = $model->unhydratedFind()
            ->select(['domain', 'singular', 'plural', 'context'])
            ->distinct()
            ->toArray();

        $entities = $model->newEntities($messages);

        $return = $model->getConnection()->transactional(
            function () use ($model, $entities, $language): bool {
                $model->deleteAll([
                    'locale' => $language,
                ]);

                foreach ($entities as $entity) {
                    $entity->set('locale', $language);
                    if ($model->save($entity) === false) {
                        return false;
                    }
                }

                return true;
            },
        );

        if ($return) {
            $this->io->out('Created ' . count($messages) . ' messages for "' . $language . '"');
        } else {
            $this->io->out('Unable to create messages for "' . $language . '"');
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

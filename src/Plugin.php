<?php
declare(strict_types=1);

namespace ADmad\I18n;

use ADmad\I18n\Command\I18nCommand;
use ADmad\I18n\Command\I18nExtractCommand;
use ADmad\I18n\Command\I18nInitCommand;
use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;

class Plugin extends BasePlugin
{
    /**
     * Do bootstrapping or not
     *
     * @var bool
     */
    protected $bootstrapEnabled = false;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected $routesEnabled = false;

    /**
     * Add console commands for the plugin.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->addMany([
            'admad/i18n' => I18nCommand::class,
            'admad/i18n extract' => I18nExtractCommand::class,
            'admad/i18n init' => I18nInitCommand::class,
        ]);
    }
}

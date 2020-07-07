<?php
declare(strict_types=1);

namespace TestApp;

use ADmad\I18n\Command\I18nExtractCommand;
use ADmad\I18n\Command\I18nInitCommand;
use Cake\Console\CommandCollection;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

class Application extends BaseApplication
{
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands = parent::console($commands);

        return $commands
            ->add('i18n extract', new I18nExtractCommand())
            ->add('i18n init', new I18nInitCommand());
    }

    public function middleware(MiddlewareQueue $middleware): MiddlewareQueue
    {
        return $middleware;
    }

    public function routes(RouteBuilder $routes): void
    {
    }
}

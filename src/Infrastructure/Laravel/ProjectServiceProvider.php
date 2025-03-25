<?php

namespace Project\Infrastructure\Laravel;

use Psr\Log\LoggerInterface;
use Project\Common\Commands\SendSmsCommand;
use Project\Common\Services\SMS\LogSmsSender;
use Project\Common\Services\SMS\SmsSenderInterface;
use Project\Common\Commands\Handlers\SendSmsHandler;
use Project\Common\ApplicationMessages\Buses\RequestBus;
use Project\Common\Services\Cookie\CookieManagerInterface;
use Project\Infrastructure\Laravel\Services\CookieManager;
use Project\Common\Services\Translator\TranslatorInterface;
use Project\Common\Services\Environment\EnvironmentInterface;
use Project\Infrastructure\Laravel\Services\LaravelTranslator;
use Project\Common\ApplicationMessages\Buses\CompositeEventBus;
use Project\Common\ApplicationMessages\Buses\CompositeRequestBus;
use Project\Common\ApplicationMessages\ApplicationMessagesManager;
use Project\Common\Services\Configuration\ApplicationConfiguration;
use Project\Common\ApplicationMessages\Events\DispatchEventsInterface;
use Project\Modules\Client\Infrastructure\Laravel\ClientsServiceProvider;
use Project\Common\ApplicationMessages\Buses\Decorators\LoggingBusDecorator;
use Project\Modules\Shopping\Infrastructure\Laravel\ShoppingServiceProvider;
use Illuminate\Contracts\Translation\Translator as LaravelTranslatorContract;
use Project\Infrastructure\Laravel\ApplicationMessages\Buses\QueueCommandBus;
use Project\Modules\Catalogue\Infrastructure\Laravel\CatalogueServiceProvider;
use Project\Modules\Administrators\Infrastructure\Laravel\AdministratorsServiceProvider;
use Project\Infrastructure\Laravel\ApplicationMessages\Buses\Decorators\TransactionBusDecorator;

class ProjectServiceProvider extends \Illuminate\Support\ServiceProvider
{
    private array $providers = [
        CatalogueServiceProvider::class,
        AdministratorsServiceProvider::class,
        ShoppingServiceProvider::class,
        ClientsServiceProvider::class,
    ];

    private array $commonCommands = [
        SendSmsCommand::class => SendSmsHandler::class,
    ];

    public array $singletons = [
        CookieManagerInterface::class => CookieManager::class,
    ];

    public function register(): void
    {
        $this->registerProviders();
        $this->registerConfiguration();
        $this->registerSmsSender();
        $this->registerBuses();
        $this->registerMessageManager();
        $this->registerCommonCommands();
    }

    private function registerProviders(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }

    private function registerConfiguration(): void
    {
        $this->app->when(ApplicationConfiguration::class)
            ->needs('$config')
            ->give(config('project.application'));
    }

    private function registerSmsSender(): void
    {
        $senders = [
            'log' => LogSmsSender::class,
        ];

        $currentSender = config('project.application.sms-sender', 'log');
        if (!array_key_exists($currentSender, $senders)) {
            $currentSender = 'log';
        }

        $this->app->singleton(SmsSenderInterface::class, $senders[$currentSender]);
    }

    private function registerBuses(): void
    {
        $this->app->singleton('CommandBus', function () {
            return new LoggingBusDecorator(
                decorated: new TransactionBusDecorator(decorated: new CompositeRequestBus),
                logger: $this->app->make(LoggerInterface::class)
            );
        });

        $this->app->singleton('QueueCommandBus', QueueCommandBus::class);

        $this->app->singleton('QueryBus', function () {
            return new LoggingBusDecorator(
                new TransactionBusDecorator(new CompositeRequestBus),
                $this->app->make(LoggerInterface::class),
            );
        });

        $this->app->singleton('EventBus', function () {
            return new LoggingBusDecorator(
                new TransactionBusDecorator(new CompositeEventBus()),
                $this->app->make(LoggerInterface::class),
            );
        });

        $this->app->resolving(DispatchEventsInterface::class, function ($object, $app) {
            $object->setDispatcher($app->make('EventBus'));
        });
    }

    private function registerMessageManager(): void
    {
        $this->app->singleton(ApplicationMessagesManager::class, function ($app) {
            return new ApplicationMessagesManager(
                $app->make('CommandBus'),
                $app->make('QueueCommandBus'),
                $app->make('QueryBus'),
                $app->make('EventBus'),
            );
        });
    }

    private function registerCommonCommands(): void
    {
        $this->app->get('CommandBus')->registerBus(new RequestBus($this->commonCommands, $this->app));
    }

    public function boot()
    {
        $this->registerTranslator();
    }

    private function registerTranslator(): void
    {
        $translationsNamespace = 'Project';
        $translationsLoader = $this->app->make(LaravelTranslatorContract::class);
        $translationsLoader->addNamespace($translationsNamespace, $this->getTranslationsDir());

        $this->app->singleton(TranslatorInterface::class, function ($app) use ($translationsNamespace) {
            return new LaravelTranslator(
                $app->make(LaravelTranslatorContract::class),
                $app->make(EnvironmentInterface::class),
                $translationsNamespace,
            );
        });
    }

    private function getTranslationsDir(): string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'Common', 'Services', 'Translator', 'Translations']);
    }
}
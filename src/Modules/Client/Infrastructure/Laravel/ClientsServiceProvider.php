<?php

namespace Project\Modules\Client\Infrastructure\Laravel;

use Project\Modules\Client\Queries;
use Project\Modules\Client\Commands;
use Illuminate\Support\Facades\Auth;
use Project\Modules\Client\Consumers;
use Project\Modules\Client\Repository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;
use Project\Modules\Client\Api\Events\ClientEvent;
use Project\Infrastructure\Laravel\Auth\AuthGuard;
use Project\Modules\Client\Auth\AuthManagerInterface;
use Project\Common\ApplicationMessages\Buses\EventBus;
use Project\Common\ApplicationMessages\Buses\RequestBus;
use Project\Modules\Shopping\Api\Events\Orders\OrderEvent;
use Project\Common\ApplicationMessages\Events\RegisteredConsumer;
use Project\Modules\Client\Entity\Confirmation\DigitCodeGenerator;
use Project\Modules\Client\Entity\Confirmation\StaticCodeGenerator;
use Project\Modules\Client\Adapters\Events\OrderCompletedDeserializer;
use Project\Modules\Client\Entity\Confirmation\CodeGeneratorInterface;
use Project\Modules\Client\Infrastructure\Laravel\Auth\GuardAuthManager;
use Project\Modules\Client\Adapters\Events\ClientConfirmationEventsDeserializer;
use Project\Modules\Client\Infrastructure\Laravel\Repository\ClientsEloquentRepository;
use Project\Modules\Client\Infrastructure\Laravel\Repository\QueryClientsEloquentRepository;

class ClientsServiceProvider extends ServiceProvider
{
    private array $commandsMapping = [
        Commands\GeneratePhoneConfirmationCommand::class => Commands\Handlers\GeneratePhoneConfirmationHandler::class,
        Commands\RefreshPhoneConfirmationCommand::class => Commands\Handlers\RefreshPhoneConfirmationHandler::class,
        Commands\ConfirmClientPhoneCommand::class => Commands\Handlers\ConfirmClientPhoneHandler::class,
        Commands\LogoutClientCommand::class => Commands\Handlers\LogoutClientHandler::class,
    ];

    private array $queriesMapping = [
        Queries\GetClientQuery::class => Queries\Handlers\GetClientHandler::class,
        Queries\GetClientsQuery::class => Queries\Handlers\GetClientsHandler::class,
        Queries\GetAuthenticatedClientQuery::class => Queries\Handlers\GetAuthenticatedClientHandler::class,
    ];

    public array $singletons = [
        Repository\ClientsRepositoryInterface::class => ClientsEloquentRepository::class,
        Repository\QueryClientsRepositoryInterface::class => QueryClientsEloquentRepository::class,
    ];

    private function getEventsMapping(): array
    {
        $confirmationEventConsumer = new RegisteredConsumer(
            Consumers\SendClientConfirmationConsumer::class,
            ClientConfirmationEventsDeserializer::class,
        );

        return [
            OrderEvent::COMPLETED->value => new RegisteredConsumer(
                Consumers\OrderCompletedConsumer::class,
                OrderCompletedDeserializer::class
            ),
            ClientEvent::CONFIRMATION_CREATED->value => $confirmationEventConsumer,
            ClientEvent::CONFIRMATION_REFRESHED->value => $confirmationEventConsumer,
        ];
    }

    public function register()
    {
        $this->registerConfirmationGenerator();
        $this->registerAuthManager();
    }

    private function registerConfirmationGenerator(): void
    {
        $generators = [
            'static' => StaticCodeGenerator::class,
            'digit' => DigitCodeGenerator::class,
        ];

        $currentGenerator = config('project.client.confirmation-generator', 'digit');
        if (!array_key_exists($currentGenerator, $generators)) {
            $currentGenerator = 'digit';
        }

        $this->app->singleton(CodeGeneratorInterface::class, $generators[$currentGenerator]);
    }

    private function registerAuthManager(): void
    {
        $this->app->singleton(AuthManagerInterface::class, function ($app) {
            return new GuardAuthManager(
                Auth::guard(AuthGuard::CLIENT->value),
                Request::session(),
                $app->make(Repository\ClientsRepositoryInterface::class),
            );
        });
    }

    public function boot()
    {
        $this->app->get('CommandBus')->registerBus(new RequestBus($this->commandsMapping, $this->app));
        $this->app->get('QueryBus')->registerBus(new RequestBus($this->queriesMapping, $this->app));
        $this->app->get('EventBus')->registerBus(new EventBus($this->getEventsMapping(), $this->app));
    }
}
<?php

namespace Project\Modules\Shopping\Order\Infrastructure\Laravel;

use Illuminate\Support\ServiceProvider;
use Project\Common\ApplicationMessages\Buses\EventBus;
use Project\Modules\Shopping\Presenters\OrderPresenter;
use Project\Common\ApplicationMessages\Buses\RequestBus;
use Project\Modules\Shopping\Order\Commands;
use Project\Modules\Shopping\Order\Queries;
use Project\Modules\Shopping\Order\Consumers;
use Project\Modules\Shopping\Api\Events\Payments\PaymentEvent;
use Project\Modules\Shopping\Presenters\OrderPresenterInterface;
use Project\Common\ApplicationMessages\Events\RegisteredConsumer;
use Project\Modules\Shopping\Adapters\Events\PaymentEventsDeserializer;
use Project\Modules\Shopping\Order\Repository\OrdersRepositoryInterface;
use Project\Modules\Shopping\Order\Repository\QueryOrdersRepositoryInterface;
use Project\Modules\Shopping\Order\Infrastructure\Laravel\Repository\OrdersEloquentRepository;
use Project\Modules\Shopping\Order\Infrastructure\Laravel\Repository\QueryOrdersEloquentRepository;

class OrdersServiceProvider extends ServiceProvider
{
    private array $commandsMapping = [
        Commands\CreateOrderCommand::class => Commands\Handlers\CreateOrderHandler::class,
        Commands\UpdateOrderCommand::class => Commands\Handlers\UpdateOrderHandler::class,
        Commands\DeleteOrderCommand::class => Commands\Handlers\DeleteOrderHandler::class,

        Commands\AddOfferCommand::class => Commands\Handlers\AddOfferHandler::class,
        Commands\UpdateOfferCommand::class => Commands\Handlers\UpdateOfferHandler::class,
        Commands\RemoveOfferCommand::class => Commands\Handlers\RemoveOfferHandler::class,

        Commands\AddPromoCommand::class => Commands\Handlers\AddPromoHandler::class,
        Commands\RemovePromoCommand::class => Commands\Handlers\RemovePromoHandler::class,

        Commands\AttachManagerCommand::class => Commands\Handlers\AttachManagerHandler::class,
        Commands\DetachManagerCommand::class => Commands\Handlers\DetachManagerHandler::class,
    ];

    private array $queriesMapping = [
        Queries\GetOrderQuery::class => Queries\Handlers\GetOrderHandler::class,
        Queries\GetOrdersQuery::class => Queries\Handlers\GetOrdersHandler::class,
    ];

    public array $singletons = [
        OrdersRepositoryInterface::class => OrdersEloquentRepository::class,
        QueryOrdersRepositoryInterface::class => QueryOrdersEloquentRepository::class,
        OrderPresenterInterface::class => OrderPresenter::class,
    ];

    private function getEventsMapping(): array
    {
        $paymentEventsConsumer = new RegisteredConsumer(
            Consumers\PaymentEventsConsumer::class,
            PaymentEventsDeserializer::class
        );

        return [
            PaymentEvent::CREATED->value => $paymentEventsConsumer,
            PaymentEvent::TRANSACTION_ADDED->value => $paymentEventsConsumer,
            PaymentEvent::STATUS_UPDATED->value => $paymentEventsConsumer,
        ];
    }

    public function boot()
    {
        $this->app->get('CommandBus')->registerBus(new RequestBus($this->commandsMapping, $this->app));
        $this->app->get('QueryBus')->registerBus(new RequestBus($this->queriesMapping, $this->app));
        $this->app->get('EventBus')->registerBus(new EventBus($this->getEventsMapping(), $this->app));
    }
}
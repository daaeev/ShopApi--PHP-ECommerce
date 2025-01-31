<?php

namespace Project\Modules\Administrators\Infrastructure\Laravel;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;
use Project\Modules\Administrators\Queries;
use Project\Modules\Administrators\Commands;
use Project\Infrastructure\Laravel\Auth\AuthGuard;
use Project\Common\ApplicationMessages\Buses\RequestBus;
use Project\Modules\Administrators\AuthManager\AuthManagerInterface;
use Project\Modules\Administrators\Repository\AdminsRepositoryInterface;
use Project\Modules\Administrators\Repository\QueryAdminsRepositoryInterface;
use Project\Modules\Administrators\Infrastructure\Laravel\AuthManager\GuardAuthManager;
use Project\Modules\Administrators\Infrastructure\Laravel\Repository\AdminsEloquentRepository;
use Project\Modules\Administrators\Infrastructure\Laravel\Repository\QueryAdminsEloquentRepository;

class AdministratorsServiceProvider extends ServiceProvider
{
    private array $commandsMapping = [
        Commands\CreateAdminCommand::class => Commands\Handlers\CreateAdminHandler::class,
        Commands\UpdateAdminCommand::class => Commands\Handlers\UpdateAdminHandler::class,
        Commands\DeleteAdminCommand::class => Commands\Handlers\DeleteAdminHandler::class,
        Commands\AuthorizeCommand::class => Commands\Handlers\AuthorizeHandler::class,
        Commands\LogoutCommand::class => Commands\Handlers\LogoutHandler::class,
    ];

    private array $queriesMapping = [
        Queries\GetAdminQuery::class => Queries\Handlers\GetAdminHandler::class,
        Queries\AdminsListQuery::class => Queries\Handlers\AdminsListHandler::class,
        Queries\AuthorizedAdminQuery::class => Queries\Handlers\AuthorizedAdminHandler::class,
    ];

    public array $singletons = [
        AdminsRepositoryInterface::class => AdminsEloquentRepository::class,
        QueryAdminsRepositoryInterface::class => QueryAdminsEloquentRepository::class,
        AuthManagerInterface::class => GuardAuthManager::class,
    ];

    public function register()
    {
        $this->app->singleton(GuardAuthManager::class, function ($app) {
            return new GuardAuthManager(
                Auth::guard(AuthGuard::ADMIN->value),
                Request::session(),
                $app->make(AdminsRepositoryInterface::class),
            );
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\MakeAdminCommand::class
            ]);
        }
        
        $this->app->get('CommandBus')->registerBus(new RequestBus($this->commandsMapping, $this->app));
        $this->app->get('QueryBus')->registerBus(new RequestBus($this->queriesMapping, $this->app));
    }
}
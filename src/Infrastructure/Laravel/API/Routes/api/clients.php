<?php

use Illuminate\Support\Facades\Route;
use Project\Common\Administrators\Role;
use Project\Infrastructure\Laravel\Auth\AuthGuard;
use Project\Infrastructure\Laravel\API\Controllers\Clients\ClientsController;

Route::prefix('clients')
    ->group(function () {
        Route::middleware('throttle:login')->group(function () {
            Route::post('confirmation', [ClientsController::class, 'generateConfirmation']);
            Route::post('login', [ClientsController::class, 'confirmPhone']);
        });

        Route::middleware('throttle:refreshClientConfirmation')
            ->patch('confirmation/refresh', [ClientsController::class, 'refreshConfirmation']);

        Route::middleware('auth:' . AuthGuard::CLIENT->value)->group(function () {
            Route::post('logout', [ClientsController::class, 'logout']);
            Route::get('', [ClientsController::class, 'getAuthenticated']);
        });
    });

Route::middleware(['auth:'. AuthGuard::ADMIN->value, 'hasAccess:' . Role::ADMIN->value])
    ->prefix('admin/clients')
    ->group(function () {
        Route::get('{id}', [ClientsController::class, 'get']);
        Route::get('', [ClientsController::class, 'list']);
    });
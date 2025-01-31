<?php

use Illuminate\Support\Facades\Route;
use Project\Common\Administrators\Role;
use Project\Infrastructure\Laravel\Auth\AuthGuard;
use Project\Infrastructure\Laravel\API\Controllers\Orders\OrdersAdminController;
use Project\Infrastructure\Laravel\API\Controllers\Orders\OrdersClientController;

Route::group(['prefix' => 'orders'], function () {
    Route::post('', [OrdersClientController::class, 'create']);
    Route::get('/{id}', [OrdersClientController::class, 'get']);
});

Route::middleware(['auth:' . AuthGuard::ADMIN->value, 'hasAccess:' . Role::MANAGER->value])
    ->prefix('admin/orders')
    ->group(function () {
        Route::get('{id}', [OrdersAdminController::class, 'get']);
        Route::get('', [OrdersAdminController::class, 'list']);

        Route::put('{id}', [OrdersAdminController::class, 'update']);
        Route::delete('{id}', [OrdersAdminController::class, 'delete']);

        Route::post('{id}/offer', [OrdersAdminController::class, 'addOffer']);
        Route::patch('{id}/offer/{offerId}', [OrdersAdminController::class, 'updateOffer']);
        Route::delete('{id}/offer/{offerId}', [OrdersAdminController::class, 'removeOffer']);

        Route::patch('{id}/promo', [OrdersAdminController::class, 'addPromo']);
        Route::delete('{id}/promo', [OrdersAdminController::class, 'removePromo']);

        Route::patch('{id}/manager', [OrdersAdminController::class, 'attachManager']);
        Route::delete('{id}/manager', [OrdersAdminController::class, 'detachManager']);
    });
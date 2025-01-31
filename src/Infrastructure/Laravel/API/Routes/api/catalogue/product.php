<?php

use Illuminate\Support\Facades\Route;
use Project\Common\Administrators\Role;
use Project\Infrastructure\Laravel\Auth\AuthGuard;
use Project\Infrastructure\Laravel\API\Controllers\Catalogue\Product\ProductController;
use Project\Infrastructure\Laravel\API\Controllers\Catalogue\Settings\SettingsController;
use Project\Infrastructure\Laravel\API\Controllers\Catalogue\ProductContent\ContentController;

Route::middleware(['auth:' . AuthGuard::ADMIN->value, 'hasAccess:' . Role::MANAGER->value])
    ->prefix('admin/catalogue/products')
    ->group(function () {
        Route::post('', [ProductController::class, 'create']);
        Route::put('{id}', [ProductController::class, 'update']);
        Route::delete('{id}', [ProductController::class, 'delete']);
        Route::get('{id}', [ProductController::class, 'get']);
        Route::get('', [ProductController::class, 'list']);

        Route::patch('{id}/content', [ContentController::class, 'updateContent']);
        Route::post('{id}/preview', [ContentController::class, 'updatePreview']);
        Route::post('{id}/image', [ContentController::class, 'addImage']);
        Route::delete('image/{id}', [ContentController::class, 'deleteImage']);

        Route::put('{id}/settings', [SettingsController::class, 'update']);
    });
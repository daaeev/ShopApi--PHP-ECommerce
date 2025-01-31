<?php

use Illuminate\Support\Facades\Route;
use Project\Common\Administrators\Role;
use Project\Infrastructure\Laravel\Auth\AuthGuard;
use Project\Infrastructure\Laravel\API\Controllers\Catalogue\Category\CategoryController;
use Project\Infrastructure\Laravel\API\Controllers\Catalogue\CategoryContent\ContentController;

Route::middleware(['auth:' . AuthGuard::ADMIN->value, 'hasAccess:' . Role::MANAGER->value])
    ->prefix('admin/catalogue/categories')
    ->group(function () {
        Route::post('', [CategoryController::class, 'create']);
        Route::put('{id}', [CategoryController::class, 'update']);
        Route::delete('{id}', [CategoryController::class, 'delete']);
        Route::get('{id}', [CategoryController::class, 'get']);
        Route::get('', [CategoryController::class, 'list']);

        Route::patch('{id}/content', [ContentController::class, 'updateContent']);
    });
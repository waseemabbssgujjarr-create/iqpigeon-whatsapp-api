<?php

use App\Http\Controllers\Api\V1\ConnectionController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Http\Controllers\Api\V1\UsageController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['request.id', 'api.key', 'partner.active', 'api.throttle', 'api.log'])
    ->group(function (): void {
        Route::get('me', MeController::class);

        Route::get('connections', [ConnectionController::class, 'index'])
            ->middleware('api.scopes:connections.read');
        Route::post('connections', [ConnectionController::class, 'store'])
            ->middleware(['api.scopes:connections.write', 'api.idempotency']);
        Route::get('connections/{id}', [ConnectionController::class, 'show'])
            ->middleware('api.scopes:connections.read');
        Route::delete('connections/{id}', [ConnectionController::class, 'destroy'])
            ->middleware('api.scopes:connections.write');

        Route::get('messages', [MessageController::class, 'index'])
            ->middleware('api.scopes:messages.read');
        Route::post('messages', [MessageController::class, 'store'])
            ->middleware(['api.scopes:messages.send', 'api.idempotency']);
        Route::get('messages/{id}', [MessageController::class, 'show'])
            ->middleware('api.scopes:messages.read');

        Route::get('templates', [TemplateController::class, 'index'])
            ->middleware('api.scopes:templates.read');
        Route::post('templates/sync', [TemplateController::class, 'sync'])
            ->middleware(['api.scopes:templates.write', 'api.idempotency']);

        Route::get('media', [MediaController::class, 'index'])
            ->middleware('api.scopes:media.read');
        Route::post('media', [MediaController::class, 'store'])
            ->middleware(['api.scopes:media.write', 'api.idempotency']);

        Route::get('webhooks', [WebhookController::class, 'index'])
            ->middleware('api.scopes:webhooks.read');
        Route::post('webhooks', [WebhookController::class, 'store'])
            ->middleware(['api.scopes:webhooks.write', 'api.idempotency']);
        Route::patch('webhooks/{id}', [WebhookController::class, 'update'])
            ->middleware(['api.scopes:webhooks.write', 'api.idempotency']);
        Route::delete('webhooks/{id}', [WebhookController::class, 'destroy'])
            ->middleware('api.scopes:webhooks.write');

        Route::get('usage', UsageController::class)
            ->middleware('api.scopes:usage.read');
    });

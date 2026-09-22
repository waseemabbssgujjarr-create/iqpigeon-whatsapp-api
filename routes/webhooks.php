<?php

use App\Http\Controllers\Webhooks\MetaWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/stripe', StripeWebhookController::class);
Route::match(['GET', 'POST'], 'webhooks/meta', MetaWebhookController::class);

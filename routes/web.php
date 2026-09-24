<?php

use App\Http\Controllers\App\ApiKeyController;
use App\Http\Controllers\App\BillingController;
use App\Http\Controllers\App\BuildCrmController;
use App\Http\Controllers\App\ConnectionController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\SettingsController;
use App\Http\Controllers\App\UsageController;
use App\Http\Controllers\App\WebhookEndpointController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\FacebookOAuthController;
use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\OAuth\MetaOAuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$marketingPlans = static function () {
    return \App\Models\Plan::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get(['slug', 'name', 'description', 'price_cents', 'currency', 'interval', 'feature_json']);
};

Route::get('/', fn () => Inertia::render('Marketing/Home', ['plans' => $marketingPlans()]))->name('home');
Route::get('/pricing', fn () => Inertia::render('Marketing/Pricing', ['plans' => $marketingPlans()]))->name('pricing');
Route::get('/features', fn () => Inertia::render('Marketing/Features'))->name('features');
Route::get('/developers', fn () => Inertia::render('Marketing/Developers'))->name('developers');
Route::get('/docs', fn () => Inertia::render('Marketing/Docs'))->name('docs');
Route::get('/contact', fn () => Inertia::render('Marketing/Contact'))->name('contact');
Route::get('/security', fn () => Inertia::render('Marketing/Security'))->name('security');
Route::get('/terms', fn () => Inertia::render('Marketing/Terms'))->name('terms');
Route::get('/privacy', fn () => Inertia::render('Marketing/Privacy'))->name('privacy');

Route::middleware('guest')->group(function (): void {
    Route::get('auth/google/redirect', [GoogleOAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleOAuthController::class, 'callback'])->name('auth.google.callback');
    Route::get('auth/facebook/redirect', [FacebookOAuthController::class, 'redirect'])->name('auth.facebook.redirect');
    Route::get('auth/facebook/callback', [FacebookOAuthController::class, 'callback'])->name('auth.facebook.callback');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('signup', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('signup', [RegisteredUserController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::get('oauth/meta/start', [MetaOAuthController::class, 'start'])->name('oauth.meta.start');
Route::get('oauth/meta/callback', [MetaOAuthController::class, 'callback'])->name('oauth.meta.callback');

Route::middleware(['auth', 'verified'])->prefix('app')->name('app.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/connections', [ConnectionController::class, 'index'])->name('connections');
    Route::post('/connections/start', [ConnectionController::class, 'start'])->name('connections.start');
    Route::post('/connections/{uuid}/continue', [ConnectionController::class, 'continueSetup'])->name('connections.continue');
    Route::delete('/connections/{uuid}', [ConnectionController::class, 'destroy'])->name('connections.destroy');
    Route::redirect('/build', '/app/build/getting-started')->name('build.index');
    Route::get('/build/getting-started', [BuildCrmController::class, 'gettingStarted'])->name('build.getting-started');
    Route::get('/build/authentication', [BuildCrmController::class, 'authentication'])->name('build.authentication');
    Route::get('/build/whatsapp-connection', [BuildCrmController::class, 'whatsAppConnection'])->name('build.whatsapp-connection');
    Route::get('/build/send-messages', [BuildCrmController::class, 'sendMessages'])->name('build.send-messages');
    Route::get('/build/receive-messages', [BuildCrmController::class, 'receiveMessages'])->name('build.receive-messages');
    Route::get('/build/message-status', [BuildCrmController::class, 'messageStatus'])->name('build.message-status');
    Route::get('/build/webhooks', [BuildCrmController::class, 'webhooks'])->name('build.webhooks');
    Route::get('/build/idempotency', [BuildCrmController::class, 'idempotency'])->name('build.idempotency');
    Route::get('/build/errors', [BuildCrmController::class, 'errors'])->name('build.errors');
    Route::get('/build/code-examples', [BuildCrmController::class, 'codeExamples'])->name('build.code-examples');
    Route::get('/build/api-reference', [BuildCrmController::class, 'apiReference'])->name('build.api-reference');
    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('/api-keys/{uuid}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
    Route::get('/webhooks', [WebhookEndpointController::class, 'index'])->name('webhooks');
    Route::post('/webhooks', [WebhookEndpointController::class, 'store'])->name('webhooks.store');
    Route::patch('/webhooks/{id}', [WebhookEndpointController::class, 'update'])->name('webhooks.update');
    Route::delete('/webhooks/{id}', [WebhookEndpointController::class, 'destroy'])->name('webhooks.destroy');
    Route::post('/webhooks/{id}/test', [WebhookEndpointController::class, 'test'])->name('webhooks.test');
    Route::get('/usage', UsageController::class)->name('usage');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::patch('/settings/integration', [SettingsController::class, 'updateIntegration'])->name('settings.integration');
    Route::post('/settings/integration-signing-secret', [SettingsController::class, 'generateIntegrationSigningSecret'])
        ->name('settings.integration-signing-secret');
});

<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Policies\ApiKeyPolicy;
use App\Policies\WhatsappConnectionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Cashier::ignoreRoutes();
    }

    public function boot(): void
    {
        Cashier::useCustomerModel(Partner::class);

        Gate::policy(WhatsappConnection::class, WhatsappConnectionPolicy::class);
        Gate::policy(ApiKey::class, ApiKeyPolicy::class);
    }
}

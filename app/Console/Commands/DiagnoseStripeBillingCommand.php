<?php

namespace App\Console\Commands;

use App\Services\Billing\StripeBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class DiagnoseStripeBillingCommand extends Command
{
    protected $signature = 'billing:diagnose-stripe';

    protected $description = 'Log safe Stripe billing configuration status (no secrets).';

    public function handle(StripeBillingService $billing): int
    {
        $key = (string) Config::get('services.stripe.key', '');
        $secret = (string) Config::get('services.stripe.secret', '');
        $webhook = (string) Config::get('services.stripe.webhook_secret', '');
        $priceId = (string) Config::get('services.stripe.price_id', '');

        $keyMode = $this->modeFromPrefix($key, 'pk_');
        $secretMode = $this->modeFromPrefix($secret, 'sk_');

        $this->info('Stripe billing configuration (safe summary)');
        $this->line('STRIPE_KEY: '.($key !== '' ? 'set ('.$keyMode.')' : 'missing'));
        $this->line('STRIPE_SECRET: '.($secret !== '' ? 'set ('.$secretMode.')' : 'missing'));
        $this->line('STRIPE_WEBHOOK_SECRET: '.($webhook !== '' ? 'set' : 'missing'));
        $this->line('STRIPE_PRICE_ID: '.($priceId !== '' ? 'set' : 'missing'));
        $this->line('Checkout ready: '.($billing->isConfigured() && $billing->hasCheckoutPrice() ? 'yes' : 'no'));

        if ($keyMode !== 'unknown' && $secretMode !== 'unknown' && $keyMode !== $secretMode) {
            $this->warn('Stripe key/secret mode mismatch (test vs live).');
        }

        return self::SUCCESS;
    }

    private function modeFromPrefix(string $value, string $expectedPrefix): string
    {
        if ($value === '') {
            return 'missing';
        }

        if (str_starts_with($value, $expectedPrefix.'test_')) {
            return 'test';
        }

        if (str_starts_with($value, $expectedPrefix.'live_')) {
            return 'live';
        }

        return 'unknown';
    }
}

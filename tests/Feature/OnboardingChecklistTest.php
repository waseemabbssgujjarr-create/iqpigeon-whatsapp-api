<?php

namespace Tests\Feature;

use App\Enums\ConnectionStatus;
use App\Enums\ProvisioningStatus;
use App\Models\Subscription;
use App\Models\UsageRecord;
use App\Models\WebhookEndpoint;
use App\Models\WhatsappConnection;
use App\Services\OnboardingChecklistService;
use Database\Seeders\ApiScopeSeeder;
use Tests\Concerns\CreatesApiPartners;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class OnboardingChecklistTest extends TestCase
{
    use CreatesApiPartners;
    use CreatesDashboardUsers;

    public function test_checklist_steps_reflect_database_state(): void
    {
        $this->seed(ApiScopeSeeder::class);

        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $partner->forceFill([
            'provisioning_status' => ProvisioningStatus::Registered,
        ])->save();

        $service = app(OnboardingChecklistService::class);
        $steps = collect($service->stepsFor($user, $partner->fresh()))->keyBy('key');

        $this->assertTrue($steps['account']['complete']);
        $this->assertTrue($steps['verify_email']['complete']);
        $this->assertFalse($steps['choose_plan']['complete']);
        $this->assertFalse($steps['payment']['complete']);
        $this->assertFalse($steps['api_key']['complete']);

        ['partner' => $apiPartner] = $this->createActivePartnerWithApiKey(['connections.read']);
        $this->assertTrue(
            collect($service->stepsFor($user, $apiPartner))->firstWhere('key', 'api_key')['complete'],
        );

        Subscription::query()->create([
            'partner_id' => $partner->id,
            'plan_id' => $apiPartner->subscriptions()->first()->plan_id,
            'type' => 'default',
            'stripe_id' => 'sub_checklist_'.uniqid(),
            'stripe_status' => \App\Enums\SubscriptionStatus::Active,
        ]);

        $partner->forceFill(['provisioning_status' => ProvisioningStatus::PaymentConfirmed])->save();

        $steps = collect($service->stepsFor($user, $partner->fresh()))->keyBy('key');
        $this->assertTrue($steps['choose_plan']['complete']);
        $this->assertTrue($steps['payment']['complete']);

        WhatsappConnection::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => 'phone_checklist_1',
            'connection_status' => ConnectionStatus::Active,
            'connected_at' => now(),
            'metadata' => [
                'cloud_api_registered_at' => now()->toIso8601String(),
            ],
        ]);

        WebhookEndpoint::query()->create([
            'partner_id' => $partner->id,
            'url' => 'https://example.com/hook',
            'secret' => str_repeat('a', 32),
            'is_active' => true,
        ]);

        UsageRecord::query()->create([
            'partner_id' => $partner->id,
            'metric' => 'api_requests',
            'quantity' => 3,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        $steps = collect($service->stepsFor($user, $partner->fresh()))->keyBy('key');
        $this->assertTrue($steps['whatsapp']['complete']);
        $this->assertTrue($steps['webhook']['complete']);
        $this->assertTrue($steps['first_request']['complete']);
    }
}

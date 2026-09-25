<?php

namespace Tests\Feature;

use App\Enums\ConnectionStatus;
use App\Enums\ProvisioningStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\WhatsappConnection;
use App\Services\IntegrationWorkflowService;
use Database\Seeders\ApiScopeSeeder;
use Tests\Concerns\CreatesApiPartners;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class IntegrationWorkflowTest extends TestCase
{
    use CreatesApiPartners;
    use CreatesDashboardUsers;

    public function test_workflow_reflects_real_partner_state(): void
    {
        $this->seed(ApiScopeSeeder::class);

        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $partner->forceFill(['provisioning_status' => ProvisioningStatus::Registered])->save();

        $service = app(IntegrationWorkflowService::class);
        $workflow = $service->forPartner($user, $partner->fresh());

        $this->assertFalse($workflow['subscription_ready']);
        $this->assertFalse($workflow['integration_complete']);
        $this->assertSame('Activate your subscription to start integrating', $workflow['headline']);

        $partner->forceFill(['provisioning_status' => ProvisioningStatus::Active])->save();

        ['partner' => $readyPartner] = $this->createActivePartnerWithApiKey(['messages.send']);

        WhatsappConnection::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'partner_id' => $readyPartner->id,
            'connection_status' => ConnectionStatus::Active,
            'phone_number_id' => '1001',
            'display_phone_number' => '+15551234567',
            'connected_at' => now(),
            'metadata' => ['cloud_api_registered_at' => now()->toIso8601String()],
        ]);

        WebhookEndpoint::query()->create([
            'partner_id' => $readyPartner->id,
            'url' => 'https://example.com/hook',
            'secret' => str_repeat('a', 32),
            'is_active' => true,
        ]);

        WebhookDelivery::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'partner_id' => $readyPartner->id,
            'webhook_endpoint_id' => $readyPartner->webhookEndpoints()->first()->id,
            'event_id' => 'evt_test_'.uniqid(),
            'event_type' => 'webhook.test',
            'payload' => [],
            'status' => WebhookDeliveryStatus::Delivered,
            'response_status' => 200,
        ]);

        $workflow = $service->forPartner($user, $readyPartner->fresh());
        $steps = collect($workflow['steps'])->keyBy('key');

        $this->assertTrue($workflow['subscription_ready']);
        $this->assertTrue($steps['whatsapp']['complete']);
        $this->assertTrue($steps['api_key']['complete']);
        $this->assertTrue($steps['webhook']['complete']);
        $this->assertTrue($steps['webhook']['meta']['webhook_tested']);
    }

    public function test_dashboard_includes_workflow_props(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['user' => $user] = $this->createVerifiedOwner();

        $this->actingAs($user)
            ->get(route('app.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Dashboard')
                ->has('workflow.steps', 4)
                ->has('crmIntegration')
                ->has('integrationHealth.checks', 6)
            );
    }
}

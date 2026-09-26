<?php

namespace Tests\Feature;

use App\Enums\ConnectionStatus;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionCredential;
use App\Services\Meta\WhatsappConnectionHydrator;
use Database\Seeders\ApiScopeSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class WhatsappConnectionPhoneOwnershipTest extends TestCase
{
    use CreatesDashboardUsers;

    public function test_release_phone_number_id_clears_sibling_connection_and_credentials(): void
    {
        $partner = Partner::factory()->create();

        $previous = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '758204954052103',
            'connection_status' => ConnectionStatus::Disconnected,
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $previous->id,
            'access_token' => 'token-a',
            'phone_number_id' => '758204954052103',
        ]);

        $owner = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Pending,
        ]);

        WhatsappConnection::releasePhoneNumberId($partner->id, '758204954052103', $owner->id);

        $previous->refresh()->load('credentials');
        $this->assertNull($previous->phone_number_id);
        $this->assertNull($previous->credentials?->phone_number_id);
    }

    public function test_disconnected_connection_does_not_reclaim_phone_from_stale_credentials(): void
    {
        $partner = Partner::factory()->create();

        $disconnected = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Disconnected,
            'phone_number_id' => null,
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $disconnected->id,
            'access_token' => 'stale-token',
            'phone_number_id' => '758204954052103',
            'waba_id' => '757872340505399',
        ]);

        WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '758204954052103',
            'connection_status' => ConnectionStatus::Active,
            'metadata' => ['cloud_api_registered_at' => now()->toIso8601String()],
        ]);

        app(WhatsappConnectionHydrator::class)->reconcileOperationalStatus($disconnected->fresh(['credentials']));

        $disconnected->refresh();
        $this->assertNull($disconnected->phone_number_id);
        $this->assertSame('758204954052103', $disconnected->credentials?->phone_number_id);
    }

    public function test_pending_connection_restores_phone_from_credentials_and_releases_conflicts(): void
    {
        $partner = Partner::factory()->create();

        $blocking = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '758204954052103',
            'connection_status' => ConnectionStatus::Disconnected,
        ]);

        $pending = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Pending,
            'phone_number_id' => null,
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $pending->id,
            'access_token' => 'token-b',
            'phone_number_id' => '758204954052103',
        ]);

        app(WhatsappConnectionHydrator::class)->reconcileOperationalStatus($pending->fresh(['credentials']));

        $blocking->refresh();
        $pending->refresh();

        $this->assertNull($blocking->phone_number_id);
        $this->assertSame('758204954052103', $pending->phone_number_id);
    }

    public function test_connections_dashboard_reconcile_does_not_violate_partner_phone_unique(): void
    {
        $this->seed([PlanSeeder::class, ApiScopeSeeder::class]);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();

        $disconnected = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Disconnected,
            'phone_number_id' => null,
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $disconnected->id,
            'access_token' => 'token-c',
            'phone_number_id' => '758204954052103',
        ]);

        WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '758204954052103',
            'connection_status' => ConnectionStatus::Active,
            'metadata' => ['cloud_api_registered_at' => now()->toIso8601String()],
        ]);

        $response = $this->actingAs($user)->get(route('app.connections'));

        $response->assertOk();

        $disconnected->refresh();
        $this->assertNull($disconnected->phone_number_id);
    }

    public function test_destroy_clears_connection_and_credential_phone_and_waba(): void
    {
        $this->seed([PlanSeeder::class, ApiScopeSeeder::class]);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();

        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Active,
            'phone_number_id' => '758204954052103',
            'waba_id' => '757872340505399',
            'metadata' => ['cloud_api_registered_at' => now()->toIso8601String()],
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'token-d',
            'phone_number_id' => '758204954052103',
            'waba_id' => '757872340505399',
        ]);

        $this->actingAs($user)
            ->delete(route('app.connections.destroy', ['uuid' => $connection->uuid]))
            ->assertRedirect(route('app.connections'));

        $connection->refresh();
        $connection->load('credentials');

        $this->assertSame(ConnectionStatus::Disconnected, $connection->connection_status);
        $this->assertNull($connection->phone_number_id);
        $this->assertNull($connection->waba_id);
        $this->assertNull($connection->credentials?->phone_number_id);
        $this->assertNull($connection->credentials?->waba_id);
    }
}

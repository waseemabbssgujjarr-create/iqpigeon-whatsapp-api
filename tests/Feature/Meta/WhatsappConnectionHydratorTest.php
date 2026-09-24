<?php

namespace Tests\Feature\Meta;

use App\Enums\ConnectionStatus;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionCredential;
use App\Services\Meta\WhatsappConnectionHydrator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsappConnectionHydratorTest extends TestCase
{
    public function test_hydration_stores_phone_number_id_and_display_phone(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Pending,
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'customer-token',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push([
                    'data' => [
                        'granular_scopes' => [
                            ['scope' => 'whatsapp_business_management', 'target_ids' => ['waba_99']],
                        ],
                    ],
                ], 200)
                ->push([
                    'data' => [
                        ['id' => '106540352242922', 'display_phone_number' => '+92 300 4522663'],
                    ],
                ], 200),
        ]);

        $hydrator = app(WhatsappConnectionHydrator::class);
        $this->assertTrue($hydrator->hydrateFromAccessToken($connection, 'customer-token'));

        $connection->refresh();
        $this->assertSame('106540352242922', $connection->phone_number_id);
        $this->assertSame('+92 300 4522663', $connection->display_phone_number);
        $this->assertSame('waba_99', $connection->waba_id);
    }

    public function test_apply_operational_status_does_not_mark_active_without_phone(): void
    {
        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Pending,
            'phone_number_id' => null,
        ]);

        app(WhatsappConnectionHydrator::class)->applyOperationalStatusAfterHydration($connection);

        $this->assertSame(ConnectionStatus::Pending, $connection->fresh()->connection_status);
        $this->assertNull($connection->fresh()->connected_at);
    }

    public function test_reconcile_demotes_active_connection_missing_phone_number_id(): void
    {
        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Active,
            'phone_number_id' => null,
            'connected_at' => now(),
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'token',
        ]);

        $changed = app(WhatsappConnectionHydrator::class)->reconcileOperationalStatus($connection);

        $this->assertTrue($changed);
        $connection->refresh();
        $this->assertSame(ConnectionStatus::Pending, $connection->connection_status);
        $this->assertNull($connection->connected_at);
    }

    public function test_rehydrate_from_stored_credentials_promotes_to_active_when_meta_returns_phone(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Active,
            'phone_number_id' => null,
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'customer-token',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push([
                    'data' => [
                        'granular_scopes' => [
                            ['scope' => 'whatsapp_business_management', 'target_ids' => ['waba_1']],
                        ],
                    ],
                ], 200)
                ->push([
                    'data' => [
                        ['id' => '999888777', 'display_phone_number' => '+15551234'],
                    ],
                ], 200),
        ]);

        $hydrator = app(WhatsappConnectionHydrator::class);
        $this->assertTrue($hydrator->rehydrateFromStoredCredentials($connection));
        $hydrator->applyOperationalStatusAfterHydration($connection->fresh());

        $connection->refresh();
        $this->assertSame(ConnectionStatus::Active, $connection->connection_status);
        $this->assertSame('999888777', $connection->phone_number_id);
    }

    public function test_model_saving_prevents_persisting_active_without_phone_number_id(): void
    {
        $partner = Partner::factory()->create();

        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Active,
            'phone_number_id' => '12345',
        ]);

        $connection->forceFill([
            'connection_status' => ConnectionStatus::Active,
            'phone_number_id' => null,
        ])->save();

        $this->assertSame(ConnectionStatus::Pending, $connection->fresh()->connection_status);
    }
}

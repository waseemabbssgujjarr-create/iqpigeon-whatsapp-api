<?php

namespace Tests\Feature\Meta;

use App\Enums\ConnectionStatus;
use App\Models\Partner;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionCredential;
use App\Services\IntegrationHealthService;
use App\Services\Meta\WhatsappCloudApiRegistrationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsappCloudApiRegistrationTest extends TestCase
{
    public function test_register_with_pin_marks_connection_send_ready(): void
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
            'phone_number_id' => '1259226987283813',
            'display_phone_number' => '+1 555-959-9302',
            'connection_status' => ConnectionStatus::Pending,
            'metadata' => ['cloud_api_registration_required' => true],
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'customer-token',
            'phone_number_id' => '1259226987283813',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['success' => true], 200)
                ->push([
                    'display_phone_number' => '+1 555-959-9302',
                    'platform_type' => 'CLOUD_API',
                    'code_verification_status' => 'VERIFIED',
                ], 200),
        ]);

        $service = app(WhatsappCloudApiRegistrationService::class);
        $result = $service->registerWithPin($connection, '123456');

        $this->assertTrue($result['ok']);
        $connection->refresh();
        $this->assertSame(ConnectionStatus::Active, $connection->connection_status);
        $this->assertNotNull(data_get($connection->metadata, 'cloud_api_registered_at'));
        $this->assertTrue($service->isRegisteredForSending($connection));
    }

    public function test_health_outbound_not_ready_without_registration(): void
    {
        $partner = Partner::factory()->create();
        WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '999',
            'connection_status' => ConnectionStatus::Active,
            'connected_at' => now(),
        ]);

        $health = app(IntegrationHealthService::class)->forPartner($partner->fresh());
        $checks = collect($health['checks'])->keyBy('key');

        $this->assertFalse($checks['whatsapp_send']['ok']);
        $this->assertFalse($checks['outbound']['ok']);
    }

    public function test_register_fails_on_meta_pin_mismatch(): void
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
            'phone_number_id' => '1259226987283813',
            'connection_status' => ConnectionStatus::Pending,
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'customer-token',
            'phone_number_id' => '1259226987283813',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Two step verification PIN Mismatch',
                    'code' => 133005,
                ],
            ], 400),
        ]);

        $service = app(WhatsappCloudApiRegistrationService::class);
        $result = $service->registerWithPin($connection, '654321');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('133005', (string) ($result['error'] ?? ''));
        $connection->refresh();
        $this->assertSame(ConnectionStatus::Pending, $connection->connection_status);
        $this->assertFalse($service->isRegisteredForSending($connection));
    }

    public function test_pin_is_never_persisted_after_registration(): void
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
            'phone_number_id' => '1259226987283813',
            'connection_status' => ConnectionStatus::Pending,
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'customer-token',
            'phone_number_id' => '1259226987283813',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push(['success' => true], 200)
                ->push(['display_phone_number' => '+1 555-959-9302'], 200),
        ]);

        $pin = '987654';
        app(WhatsappCloudApiRegistrationService::class)->registerWithPin($connection, $pin);

        $connection->refresh();
        $credential = WhatsappConnectionCredential::query()->where('whatsapp_connection_id', $connection->id)->first();

        $encoded = json_encode([
            $connection->metadata,
            $credential?->metadata,
            $connection->getAttributes(),
            $credential?->getAttributes(),
        ]);

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString($pin, $encoded);
    }

    public function test_register_does_not_log_pin(): void
    {
        config([
            'services.meta.app_id' => 'app-id',
            'services.meta.app_secret' => 'app-secret',
            'services.meta.graph_version' => 'v21.0',
        ]);

        $logEntries = [];
        Log::listen(function ($level, $message, $context) use (&$logEntries): void {
            $logEntries[] = [
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ];
        });

        $partner = Partner::factory()->create();
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '1259226987283813',
            'connection_status' => ConnectionStatus::Pending,
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'customer-token',
            'phone_number_id' => '1259226987283813',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Registration failed', 'code' => 131000],
            ], 400),
        ]);

        $pin = '112233';
        app(WhatsappCloudApiRegistrationService::class)->registerWithPin($connection, $pin);

        $this->assertNotEmpty($logEntries, 'Expected a warning log on failed registration.');
        foreach ($logEntries as $entry) {
            $this->assertStringNotContainsString(
                $pin,
                json_encode($entry),
                'PIN must not appear in application logs.',
            );
        }

        Http::assertSent(function ($request) use ($pin) {
            return str_contains($request->url(), '/1259226987283813/register')
                && ($request->data()['pin'] ?? null) === $pin
                && ($request->data()['messaging_product'] ?? null) === 'whatsapp';
        });
    }

    public function test_health_outbound_ready_after_successful_registration(): void
    {
        $partner = Partner::factory()->create();
        WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => '1259226987283813',
            'connection_status' => ConnectionStatus::Active,
            'connected_at' => now(),
            'metadata' => [
                'cloud_api_registered_at' => now()->toIso8601String(),
                'cloud_api_register_confirmed' => true,
            ],
        ]);

        $health = app(IntegrationHealthService::class)->forPartner($partner->fresh());
        $checks = collect($health['checks'])->keyBy('key');

        $this->assertTrue($checks['whatsapp_send']['ok']);
        $this->assertTrue($checks['outbound']['ok']);
    }
}

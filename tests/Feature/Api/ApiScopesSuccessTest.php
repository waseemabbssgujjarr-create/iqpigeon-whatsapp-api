<?php

namespace Tests\Feature\Api;

use App\Enums\ConnectionStatus;
use App\Models\Partner;
use App\Models\UsageRecord;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionCredential;
use Database\Seeders\ApiScopeSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class ApiScopesSuccessTest extends TestCase
{
    use CreatesApiPartners;

    private ?WhatsappConnection $activeConnection = null;

    #[DataProvider('scopeSuccessProvider')]
    public function test_scope_allows_successful_request(
        string $scope,
        string $method,
        string $uri,
        ?callable $setup = null,
        array $payload = [],
        bool $needsIdempotency = false,
    ): void {
        $this->seed(ApiScopeSeeder::class);
        $this->activeConnection = null;

        ['partner' => $partner, 'secret' => $secret] = $this->createActivePartnerWithApiKey([$scope]);

        if (in_array($scope, ['messages.send', 'media.write'], true)) {
            Queue::fake();
            Http::fake([
                'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.scope']]], 200),
            ]);
        }

        if ($setup !== null) {
            $setup($this, $partner);
        }

        if ($this->activeConnection !== null && ($payload['connection_id'] ?? null) === true) {
            $payload['connection_id'] = $this->activeConnection->uuid;
        }

        $headers = $this->withBearer($secret);

        if ($needsIdempotency) {
            $headers['Idempotency-Key'] = 'scope-'.Str::slug($scope).'-'.uniqid();
        }

        $response = match (strtoupper($method)) {
            'GET' => $this->getJson($uri, $headers),
            'POST' => $this->postJson($uri, $payload, $headers),
            'PATCH' => $this->patchJson($uri, $payload, $headers),
            'DELETE' => $this->deleteJson($uri, [], $headers),
            default => $this->json($method, $uri, $payload, $headers),
        };

        $response->assertSuccessful();
    }

    public static function scopeSuccessProvider(): array
    {
        return [
            'connections.read' => ['connections.read', 'GET', '/api/v1/connections'],
            'connections.write' => [
                'connections.write',
                'POST',
                '/api/v1/connections',
                null,
                ['external_ref' => 'scope-ref'],
                true,
            ],
            'messages.read' => ['messages.read', 'GET', '/api/v1/messages'],
            'messages.send' => [
                'messages.send',
                'POST',
                '/api/v1/messages',
                fn (self $test, Partner $partner) => $test->seedActiveConnection($partner),
                [
                    'connection_id' => true,
                    'to' => '15551234567',
                    'type' => 'text',
                    'body' => 'Hello from scope test',
                ],
                true,
            ],
            'templates.read' => [
                'templates.read',
                'GET',
                '/api/v1/templates',
                function (self $test, Partner $partner): void {
                    Http::fake([
                        'graph.facebook.com/*' => Http::response(['data' => []], 200),
                    ]);
                    $test->seedActiveConnection($partner, withWaba: true);
                },
            ],
            'templates.write' => [
                'templates.write',
                'POST',
                '/api/v1/templates/sync',
                null,
                [],
                true,
            ],
            'media.read' => ['media.read', 'GET', '/api/v1/media'],
            'media.write' => [
                'media.write',
                'POST',
                '/api/v1/media',
                fn (self $test, Partner $partner) => $test->seedActiveConnection($partner),
                [
                    'connection_id' => true,
                    'to' => '15551234567',
                    'type' => 'image',
                    'media' => ['link' => 'https://example.com/image.jpg'],
                ],
                true,
            ],
            'webhooks.read' => ['webhooks.read', 'GET', '/api/v1/webhooks'],
            'webhooks.write' => [
                'webhooks.write',
                'POST',
                '/api/v1/webhooks',
                null,
                [
                    'url' => 'https://example.com/hooks/scope-test',
                    'events' => ['message.sent'],
                ],
                true,
            ],
            'usage.read' => [
                'usage.read',
                'GET',
                '/api/v1/usage',
                function (self $test, Partner $partner): void {
                    UsageRecord::query()->create([
                        'partner_id' => $partner->id,
                        'metric' => 'api_requests',
                        'quantity' => 1,
                        'period_start' => now()->startOfMonth(),
                        'period_end' => now()->endOfMonth(),
                    ]);
                },
            ],
        ];
    }

    private function seedActiveConnection(Partner $partner, bool $withWaba = false): void
    {
        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'external_ref' => 'test-'.Str::random(8),
            'waba_id' => $withWaba ? 'waba_123' : null,
            'phone_number_id' => 'phone_123',
            'display_phone_number' => '+15551234567',
            'connection_status' => ConnectionStatus::Active,
            'connected_at' => now(),
        ]);

        WhatsappConnectionCredential::query()->create([
            'whatsapp_connection_id' => $connection->id,
            'access_token' => 'test-access-token',
            'phone_number_id' => 'phone_123',
            'waba_id' => $withWaba ? 'waba_123' : null,
        ]);

        $this->activeConnection = $connection;
    }
}

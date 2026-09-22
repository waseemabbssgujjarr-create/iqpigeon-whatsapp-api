<?php

namespace Tests\Feature;

use Database\Seeders\ApiScopeSeeder;
use Tests\Concerns\CreatesApiPartners;
use Tests\TestCase;

class ApiScopeEnforcementTest extends TestCase
{
    use CreatesApiPartners;

    #[\PHPUnit\Framework\Attributes\DataProvider('scopedRoutesProvider')]
    public function test_missing_scope_returns_forbidden(string $method, string $uri, string $requiredScope): void
    {
        $this->seed(ApiScopeSeeder::class);

        ['secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.read']);

        $response = match (strtoupper($method)) {
            'GET' => $this->getJson($uri, $this->withBearer($secret)),
            'POST' => $this->postJson($uri, [], $this->withBearer($secret)),
            default => $this->json($method, $uri, [], $this->withBearer($secret)),
        };

        $response->assertForbidden()
            ->assertJsonPath('error.code', 'insufficient_scope');
    }

    public static function scopedRoutesProvider(): array
    {
        return [
            ['POST', '/api/v1/messages', 'messages.send'],
            ['GET', '/api/v1/messages', 'messages.read'],
            ['POST', '/api/v1/connections', 'connections.write'],
            ['GET', '/api/v1/webhooks', 'webhooks.read'],
            ['POST', '/api/v1/webhooks', 'webhooks.write'],
            ['GET', '/api/v1/usage', 'usage.read'],
        ];
    }
}

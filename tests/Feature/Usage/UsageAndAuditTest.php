<?php

namespace Tests\Feature\Usage;

use App\Models\ApiKey;
use App\Models\ApiRequest;
use App\Models\AuditLog;
use App\Services\ApiKeyService;
use App\Services\AuditLogService;
use Database\Seeders\ApiScopeSeeder;
use Tests\Concerns\CreatesApiPartners;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class UsageAndAuditTest extends TestCase
{
    use CreatesApiPartners;
    use CreatesDashboardUsers;

    public function test_authenticated_api_request_is_logged(): void
    {
        ['secret' => $secret] = $this->createActivePartnerWithApiKey(['connections.read']);

        $this->getJson('/api/v1/me', $this->withBearer($secret))->assertOk();

        $this->assertSame(1, ApiRequest::query()->count());
        $this->assertSame(200, ApiRequest::query()->value('status_code'));
    }

    public function test_dashboard_api_key_create_writes_audit_log(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();

        $this->actingAs($user)->post('/app/api-keys', [
            'label' => 'Dashboard key',
            'scopes' => ['connections.read'],
        ])->assertRedirect(route('app.api-keys'));

        $this->assertDatabaseHas('audit_logs', [
            'partner_id' => $partner->id,
            'user_id' => $user->id,
            'action' => 'api_key.created',
        ]);
    }

    public function test_api_key_service_create_can_be_audited(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['partner' => $partner] = $this->createActivePartnerWithApiKey();

        $generated = app(ApiKeyService::class)->generate($partner, 'Service key', ['usage.read']);

        app(AuditLogService::class)->log(
            'api_key.created',
            $partner,
            subject: $generated['api_key'],
            ip: '127.0.0.1',
        );

        $this->assertTrue(
            AuditLog::query()
                ->where('partner_id', $partner->id)
                ->where('action', 'api_key.created')
                ->where('subject_type', ApiKey::class)
                ->exists(),
        );
    }
}

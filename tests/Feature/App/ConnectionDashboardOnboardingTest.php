<?php

namespace Tests\Feature\App;

use App\Models\EmbeddedSignupSession;
use App\Enums\ConnectionStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WhatsappConnection;
use Database\Seeders\ApiScopeSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Str;
use Inertia\Support\Header;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class ConnectionDashboardOnboardingTest extends TestCase
{
    use CreatesDashboardUsers;

    /**
     * @return array<string, string>
     */
    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html, application/xhtml+xml',
        ];
    }

    private function seedOperationalPartnerSubscription(\App\Models\Partner $partner, ?int $maxConnections = null): void
    {
        $this->seed(PlanSeeder::class);
        $plan = Plan::query()->where('slug', 'platform')->firstOrFail();

        if ($maxConnections !== null) {
            $features = is_array($plan->feature_json) ? $plan->feature_json : [];
            $features['max_connections'] = $maxConnections;
            $plan->forceFill(['feature_json' => $features])->save();
        }

        Subscription::query()->create([
            'partner_id' => $partner->id,
            'plan_id' => $plan->id,
            'type' => 'default',
            'stripe_id' => 'sub_test_'.uniqid(),
            'stripe_status' => SubscriptionStatus::Active,
        ]);
    }

    public function test_inertia_start_returns_external_location_with_onboarding_url(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seedOperationalPartnerSubscription($partner);

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->post(route('app.connections.start'));

        $response->assertStatus(409);
        $this->assertTrue($response->headers->has(Header::LOCATION));
        $location = (string) $response->headers->get(Header::LOCATION);
        $this->assertStringContainsString('/oauth/meta/start?token=', $location);
    }

    public function test_inertia_continue_setup_returns_external_location_with_onboarding_url(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seedOperationalPartnerSubscription($partner);

        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Pending,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->post(route('app.connections.continue', ['uuid' => $connection->uuid]));

        $response->assertStatus(409);
        $this->assertTrue($response->headers->has(Header::LOCATION));
        $location = (string) $response->headers->get(Header::LOCATION);
        $this->assertStringContainsString('/oauth/meta/start?token=', $location);
    }

    public function test_continue_setup_for_coexistence_draft_uses_js_flash_not_oauth_redirect(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seedOperationalPartnerSubscription($partner);

        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Pending,
            'metadata' => ['onboarding_source' => 'coexistence'],
        ]);

        EmbeddedSignupSession::query()->create([
            'partner_id' => $partner->id,
            'whatsapp_connection_id' => $connection->id,
            'state_token_hash' => hash('sha256', 'old'),
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->post(route('app.connections.continue', ['uuid' => $connection->uuid]));

        $response->assertRedirect(route('app.connections'));
        $response->assertSessionHas('coexistence_onboarding.connection_uuid', $connection->uuid);
        $response->assertSessionHas('coexistence_onboarding.session_token');
    }

    public function test_start_coexistence_does_not_redirect_to_oauth_meta_start(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seedOperationalPartnerSubscription($partner);

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->post(route('app.connections.start-coexistence'));

        $response->assertRedirect(route('app.connections'));
        $response->assertSessionHas('coexistence_onboarding.session_token');
        $this->assertFalse($response->headers->has(Header::LOCATION));
    }

    public function test_non_inertia_start_uses_external_redirect(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seedOperationalPartnerSubscription($partner);

        $response = $this->actingAs($user)->post(route('app.connections.start'));

        $response->assertRedirect();
        $this->assertStringContainsString('/oauth/meta/start?token=', (string) $response->headers->get('Location'));
        $this->assertFalse($response->headers->has(Header::LOCATION));
    }

    public function test_start_fails_when_connection_limit_reached(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seedOperationalPartnerSubscription($partner, maxConnections: 1);

        WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'connection_status' => ConnectionStatus::Pending,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->post(route('app.connections.start'));

        $response->assertRedirect(route('app.connections'));
        $response->assertSessionHasErrors('connect');
    }

    public function test_continue_setup_rejects_non_pending_connection(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seedOperationalPartnerSubscription($partner);

        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'phone_number_id' => 'phone_active_1',
            'connection_status' => ConnectionStatus::Active,
            'display_phone_number' => '+15551234567',
            'connected_at' => now(),
            'metadata' => [
                'cloud_api_registered_at' => now()->toIso8601String(),
            ],
        ]);

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->post(route('app.connections.continue', ['uuid' => $connection->uuid]));

        $response->assertRedirect(route('app.connections'));
        $response->assertSessionHasErrors('connect');
    }

    public function test_continue_setup_requires_partner_ownership(): void
    {
        $this->seed(ApiScopeSeeder::class);
        ['user' => $user, 'partner' => $partner] = $this->createVerifiedOwner();
        $this->seedOperationalPartnerSubscription($partner);

        $otherUser = User::factory()->create();
        $otherPartner = \App\Models\Partner::factory()->create(['owner_user_id' => $otherUser->id]);
        $otherUser->forceFill(['partner_id' => $otherPartner->id])->save();

        $connection = WhatsappConnection::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $otherPartner->id,
            'connection_status' => ConnectionStatus::Pending,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders($this->inertiaHeaders())
            ->post(route('app.connections.continue', ['uuid' => $connection->uuid]));

        $response->assertNotFound();
    }

    public function test_start_requires_partner_owner(): void
    {
        $this->seed(ApiScopeSeeder::class);
        $owner = User::factory()->create();
        $partner = \App\Models\Partner::factory()->create(['owner_user_id' => $owner->id]);
        $member = User::factory()->create(['partner_id' => $partner->id]);
        $this->seedOperationalPartnerSubscription($partner);

        $response = $this->actingAs($member)
            ->withHeaders($this->inertiaHeaders())
            ->post(route('app.connections.start'));

        $response->assertForbidden();
    }
}

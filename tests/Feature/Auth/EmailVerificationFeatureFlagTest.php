<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\OnboardingChecklistService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationFeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_key_is_defined_and_is_boolean(): void
    {
        $this->assertNotNull(config('auth.email_verification_enabled'));
        $this->assertIsBool(config('auth.email_verification_enabled'));
    }

    public function test_unverified_user_can_access_app_when_verification_disabled(): void
    {
        Config::set('auth.email_verification_enabled', false);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/app')->assertOk();
    }

    public function test_unverified_user_is_blocked_from_app_when_verification_enabled(): void
    {
        Config::set('auth.email_verification_enabled', true);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/app')->assertRedirect(route('verification.notice'));
    }

    public function test_signup_verifies_email_and_skips_notice_when_disabled(): void
    {
        Notification::fake();
        Config::set('auth.email_verification_enabled', false);

        $response = $this->post('/signup', [
            'name' => 'Dev Owner',
            'company' => 'Dev CRM Co',
            'email' => 'dev-owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('app.dashboard'));

        $user = User::query()->where('email', 'dev-owner@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);

        Notification::assertNothingSent();

        $this->actingAs($user)->get('/app')->assertOk();
        $this->actingAs($user)->get('/verify-email')->assertRedirect(route('app.dashboard'));
    }

    public function test_signup_leaves_email_unverified_when_enabled(): void
    {
        Notification::fake();
        Config::set('auth.email_verification_enabled', true);

        $this->post('/signup', [
            'name' => 'Verify Owner',
            'company' => 'Verify CRM',
            'email' => 'verify-owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('app.dashboard'));

        $user = User::query()->where('email', 'verify-owner@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_onboarding_marks_verify_step_disabled_when_flag_off(): void
    {
        Config::set('auth.email_verification_enabled', false);

        $user = User::factory()->unverified()->create(['partner_id' => null]);

        $step = collect(app(OnboardingChecklistService::class)->stepsFor($user, null))
            ->firstWhere('key', 'verify_email');

        $this->assertTrue($step['complete']);
        $this->assertTrue($step['disabled']);
        $this->assertNull($step['href']);
        $this->assertStringContainsString('disabled', $step['label']);
    }
}

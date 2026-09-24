<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationAndVerificationTest extends TestCase
{
    use RefreshDatabase;

    private mixed $originalEmailVerificationEnabled = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEmailVerificationEnabled = config('auth.email_verification_enabled');
        Config::set('auth.email_verification_enabled', true);
    }

    protected function tearDown(): void
    {
        if ($this->originalEmailVerificationEnabled !== null) {
            Config::set('auth.email_verification_enabled', $this->originalEmailVerificationEnabled);
        }

        parent::tearDown();
    }

    public function test_registration_creates_partner_and_redirects(): void
    {
        $response = $this->post('/signup', [
            'name' => 'Alex Owner',
            'company' => 'Acme CRM',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('app.dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
        $this->assertDatabaseHas('partners', ['name' => 'Acme CRM']);
    }

    public function test_unverified_user_is_redirected_from_app(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/app')->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/app')->assertOk();
    }

    public function test_email_can_be_verified(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($verificationUrl)->assertRedirect(route('app.dashboard').'?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_notification_can_be_resent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}

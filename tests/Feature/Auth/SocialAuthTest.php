<?php

namespace Tests\Feature\Auth;

use App\Models\OAuthIdentity;
use App\Models\User;
use App\Services\Auth\SocialAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_oauth_routes_are_registered(): void
    {
        $this->assertNotNull(app('router')->getRoutes()->getByName('auth.google.redirect'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('auth.google.callback'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('auth.facebook.redirect'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('auth.facebook.callback'));
    }

    public function test_authenticated_user_cannot_start_google_oauth(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/auth/google/redirect')->assertRedirect();
    }

    public function test_google_redirect_when_unconfigured_sends_user_to_login(): void
    {
        Config::set('services.google.client_id', '');
        Config::set('services.google.client_secret', '');

        $this->get('/auth/google/redirect')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_facebook_callback_route_exists(): void
    {
        $this->assertTrue(route('auth.facebook.callback') !== '');
    }

    public function test_social_auth_flags_are_env_based_and_not_in_login_page_secrets(): void
    {
        Config::set('services.google.client_id', '123456789012-abc.apps.googleusercontent.com');
        Config::set('services.google.client_secret', 'test-secret-not-in-html');
        Config::set('services.facebook.client_id', 'fb-app-id');
        Config::set('services.facebook.client_secret', 'fb-secret-not-in-html');

        $response = $this->get('/login');
        $response->assertOk();
        $response->assertDontSee('test-secret-not-in-html', false);
        $response->assertDontSee('fb-secret-not-in-html', false);
    }

    public function test_social_auth_links_existing_user_by_email(): void
    {
        $user = User::factory()->create(['email' => 'john@example.com']);

        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn('google-123');
        $socialUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $socialUser->shouldReceive('getName')->andReturn('John');
        $socialUser->shouldReceive('getAvatar')->andReturn(null);
        $socialUser->shouldReceive('getRaw')->andReturn(['email_verified' => true]);

        $resolved = app(SocialAuthService::class)->findOrCreateUser('google', $socialUser);

        $this->assertSame($user->id, $resolved->id);
        $this->assertDatabaseHas('oauth_identities', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-123',
        ]);
    }

    public function test_invalid_google_state_redirects_to_login(): void
    {
        Config::set('services.google.client_id', '123456789012-abc.apps.googleusercontent.com');
        Config::set('services.google.client_secret', 'secret');
        Config::set('services.google.redirect', 'http://localhost/auth/google/callback');

        $mock = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $mock->shouldReceive('user')->andThrow(new \Laravel\Socialite\Two\InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($mock);

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

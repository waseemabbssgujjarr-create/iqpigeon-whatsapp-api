<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestAuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_and_signup(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/signup')->assertOk();
    }

    public function test_authenticated_user_is_redirected_from_login_to_app_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('app.dashboard'));
    }

    public function test_authenticated_user_is_redirected_from_signup_to_app_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/signup')
            ->assertRedirect(route('app.dashboard'));
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    public function test_user_can_logout_and_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }
}

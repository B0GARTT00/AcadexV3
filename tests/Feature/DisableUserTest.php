<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class DisableUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_disable_user_without_disabled_until_column()
    {
        // Create admin and normal user
        $admin = User::factory()->create(['role' => 3]);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->postJson(route('users.disable', ['user' => $user->id]), ['duration' => '1_week'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Ensure the user is marked inactive
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserDisableEnableTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_disable_and_enable_user()
    {
        // Create admin and normal user
        $admin = User::factory()->create(['role' => 3]);
        $user = User::factory()->create(['is_active' => true]);

        // Disable the user
        $this->actingAs($admin)
            ->postJson(route('admin.users.disable', ['user' => $user->id]), ['duration' => '1_week'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);

        // Re-enable the user
        $this->actingAs($admin)
            ->postJson(route('admin.users.enable', ['user' => $user->id]))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => true]);
        if (Schema::hasColumn('users', 'disabled_until')) {
            $this->assertDatabaseHas('users', ['id' => $user->id, 'disabled_until' => null]);
        }
    }

    public function test_admin_can_set_indefinite_disable()
    {
        $admin = User::factory()->create(['role' => 3]);
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.users.disable', ['user' => $user->id]), ['duration' => 'indefinite'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertFalse($user->is_active);
        if (Schema::hasColumn('users', 'disabled_until')) {
            $this->assertEquals(9999, (new \Carbon\Carbon($user->disabled_until))->year);
        }
    }
}

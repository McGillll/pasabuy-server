<?php

namespace Tests\Feature;

use App\Models\PasabuyOrder;
use App\Models\PasabuySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperadminTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_login_token_carries_admin_and_customer_abilities()
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'password' => 'password123']);

        $this->postJson('/api/auth/login', [
            'email' => $superadmin->email,
            'password' => 'password123',
        ])->assertOk();

        $token = $superadmin->tokens()->first();

        $this->assertEqualsCanonicalizing(['superadmin', 'admin', 'customer'], $token->abilities);
    }

    public function test_superadmin_can_revert_a_finished_schedule_to_closed()
    {
        $seller = User::factory()->create(['role' => 'admin']);
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $schedule = PasabuySchedule::factory()->create([
            'user_id' => $seller->id,
            'status' => 'finished',
            'finished_at' => now(),
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        $order = PasabuyOrder::factory()->create([
            'pasabuy_schedule_id' => $schedule->id,
            'invoice_sent_at' => now(),
        ]);

        Sanctum::actingAs($superadmin, $superadmin->abilities());

        $this->postJson("/api/admin/pasabuy-schedules/{$schedule->uuid}/close")
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $schedule->refresh();
        $this->assertNull($schedule->finished_at);
        $this->assertNull($schedule->due_date);
        $this->assertNull($order->fresh()->invoice_sent_at);
    }

    public function test_admin_cannot_revert_a_finished_schedule()
    {
        $seller = User::factory()->create(['role' => 'admin']);
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id, 'status' => 'finished']);

        Sanctum::actingAs($seller, $seller->abilities());

        $this->postJson("/api/admin/pasabuy-schedules/{$schedule->uuid}/close")
            ->assertStatus(422);
    }

    public function test_superadmin_can_delete_a_finished_schedule_of_another_seller()
    {
        $seller = User::factory()->create(['role' => 'admin']);
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id, 'status' => 'finished']);

        Sanctum::actingAs($superadmin, $superadmin->abilities());

        $this->deleteJson("/api/admin/pasabuy-schedules/{$schedule->uuid}")->assertOk();

        $this->assertDatabaseMissing('pasabuy_schedules', ['id' => $schedule->id]);
    }

    public function test_superadmin_can_edit_a_finished_schedule()
    {
        $seller = User::factory()->create(['role' => 'admin']);
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id, 'status' => 'finished']);

        Sanctum::actingAs($superadmin, $superadmin->abilities());

        $this->putJson("/api/admin/pasabuy-schedules/{$schedule->uuid}", [
            'title' => 'Renamed run',
            'pasabuy_schedule_date' => now()->addWeek()->toDateString(),
        ])->assertOk()
            ->assertJsonPath('data.title', 'Renamed run');
    }

    public function test_superadmin_sees_every_sellers_schedules()
    {
        $sellerA = User::factory()->create(['role' => 'admin']);
        $sellerB = User::factory()->create(['role' => 'admin']);
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        PasabuySchedule::factory()->create(['user_id' => $sellerA->id]);
        PasabuySchedule::factory()->create(['user_id' => $sellerB->id]);

        Sanctum::actingAs($superadmin, $superadmin->abilities());

        $this->getJson('/api/admin/pasabuy-schedules')
            ->assertOk()
            ->assertJsonPath('data.total', 2);
    }

    public function test_superadmin_can_reach_customer_endpoints()
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        Sanctum::actingAs($superadmin, $superadmin->abilities());

        $this->getJson('/api/customer/basket')->assertOk();
    }
}

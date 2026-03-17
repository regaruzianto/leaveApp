<?php

namespace Tests\Feature;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private LeaveType $annualLeave;
    private LeaveType $sickLeave;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user  = User::factory()->create(['role' => 'user']);

        $this->annualLeave = LeaveType::create(['name' => 'Annual Leave', 'default_quota' => 12]);
        $this->sickLeave   = LeaveType::create(['name' => 'Sick Leave', 'default_quota' => 6]);

        // Assign balance to the user
        LeaveBalance::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->annualLeave->id,
            'year'          => now()->year,
            'total_quota'   => 12,
            'used'          => 0,
        ]);

        LeaveBalance::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->sickLeave->id,
            'year'          => now()->year,
            'total_quota'   => 6,
            'used'          => 0,
        ]);
    }

    /** @test */
    public function user_can_submit_leave_request(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/leave-requests', [
            'leave_type_id' => $this->annualLeave->id,
            'start_date'    => now()->addDays(5)->format('Y-m-d'),
            'end_date'      => now()->addDays(7)->format('Y-m-d'),
            'reason'        => 'Liburan keluarga',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_days', 3);
    }

    /** @test */
    public function user_cannot_submit_leave_request_with_insufficient_quota(): void
    {
        // Use up all quota
        LeaveBalance::where('user_id', $this->user->id)
            ->where('leave_type_id', $this->annualLeave->id)
            ->update(['used' => 12]);

        $response = $this->actingAs($this->user)->postJson('/api/leave-requests', [
            'leave_type_id' => $this->annualLeave->id,
            'start_date'    => now()->addDays(1)->format('Y-m-d'),
            'end_date'      => now()->addDays(3)->format('Y-m-d'),
            'reason'        => 'Liburan',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.total_days.0', fn($v) => str_contains($v, 'Kuota tidak cukup'));
    }


}

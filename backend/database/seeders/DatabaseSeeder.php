<?php

namespace Database\Seeders;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Creates:
     *   - 1 Admin user
     *   - 2 Leave types: Annual Leave (12 days), Sick Leave (6 days)
     */
    public function run(): void
    {
        // Create Leave Types
        $annualLeave = LeaveType::firstOrCreate(
            ['name' => 'Annual Leave'],
            ['default_quota' => 12]
        );

        $sickLeave = LeaveType::firstOrCreate(
            ['name' => 'Sick Leave'],
            ['default_quota' => 6]
        );

        // Create Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@energeek.id'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
            ]
        );

        $this->command->info('✅ Admin user created: admin@energeek.id / password123');
        $this->command->info('✅ Leave types seeded: Annual Leave (12 days), Sick Leave (6 days)');
    }
}

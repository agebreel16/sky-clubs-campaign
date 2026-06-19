<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@skyclubs.test'],
            [
                'id'                       => Str::uuid()->toString(),
                'name'                     => 'Super Admin',
                'email'                    => 'admin@skyclubs.test',
                'password'                 => bcrypt('Admin@12345'),
                'employee_id'              => 'SA-001',
                'department'               => 'admin',
                'role'                     => 'super_admin',
                'position'                 => 'Campaign Manager',
                'is_active'                => true,
                'requires_password_change' => false,
                'email_verified_at'        => now(),
                'two_factor_enabled'       => false,
            ]
        );

        $this->command->info('✅ Super Admin created:');
        $this->command->line('   Email:    admin@skyclubs.test');
        $this->command->line('   Password: Admin@12345');
        $this->command->line('   Role:     super_admin');
        $this->command->line('   URL:      http://sky-clubs.test/admin');
    }
}

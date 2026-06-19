<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder
 *
 * Master seeder for the Sky Clubs Campaign system.
 * Run with: php artisan db:seed
 *
 * Seeding order is intentional — respects FK dependencies:
 *   1. clubs                    (no dependencies)
 *   2. roles                    (no dependencies)
 *   3. permissions              (no dependencies)
 *   4. role_has_permissions     (depends on roles + permissions → seeded inside RolesAndPermissionsSeeder)
 *
 * NOTE: agents, opportunities, rewards, history_log, data_imports,
 *       daily_snapshots, notifications, users, and audit_logs are
 *       NOT seeded here — they are populated through the data import workflow.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  Sky Clubs Campaign — Database Seeder');
        $this->command->info('  Campaign: May 1, 2026 → April 30, 2027');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('');

        $this->call([
            ClubsSeeder::class,
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            AppSettingsSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  ✅ All seeders completed successfully.');
        $this->command->info('  Login: admin@skyclubs.test / Admin@12345');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('');
    }
}

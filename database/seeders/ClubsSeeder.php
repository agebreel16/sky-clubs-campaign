<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ClubsSeeder
 *
 * Seeds the 3 default clubs for the Sky Clubs Campaign (May 2026 – April 2027).
 *
 * ⚠️  CRITICAL: These values are the master configuration for the entire campaign.
 *     Any change to these values affects ALL agents immediately.
 *     Coordinate with the campaign manager before modifying.
 *
 * Club Summary:
 * ┌─────────────────┬────────┬──────────┬──────────┬────────────┬────────────┬───────────┬───────────┬──────────────┐
 * │ Club Name       │ Order  │ Required │ Transfer │ Base Bonus │ 1st Arrival│ Min Seats │ Prize     │ Entry Tickets│
 * ├─────────────────┼────────┼──────────┼──────────┼────────────┼────────────┼───────────┼───────────┼──────────────┤
 * │ نادي الانطلاق   │   1    │    25    │    15    │  300 NIS   │  600 NIS   │    90     │ 15,000 NIS│      1       │
 * │ نادي التفوق     │   2    │    50    │    30    │  700 NIS   │ 1,500 NIS  │    55     │ 35,000 NIS│      2       │
 * │ نادي القمة      │   3    │   100    │    60    │ 1,000 NIS  │ 2,000 NIS  │    45     │ 70,000 NIS│      3       │
 * └─────────────────┴────────┴──────────┴──────────┴────────────┴────────────┴───────────┴───────────┴──────────────┘
 *
 * All monetary values are in NIS (New Israeli Shekels).
 * required_transfer_percentage = 0.60 (60% rule) for ALL clubs.
 */
class ClubsSeeder extends Seeder
{
    /**
     * Run the seeder.
     * Uses upsert to safely re-run without duplicate errors.
     */
    public function run(): void
    {
        $clubs = [
            // ─── Club 1: نادي الانطلاق ───────────────────────────────────────────
            [
                'club_id'                        => Str::uuid()->toString(),
                'club_name'                      => 'نادي الانطلاق',
                'club_order'                     => 1,
                'required_increase'              => 25,                    // new lines to enter
                'required_transfer_count'        => 15,                    // min transfer lines
                'required_transfer_percentage'   => 0.60,                  // 60% rule
                'base_reward_amount'             => 300.00,                // NIS entry bonus
                'first_arrival_reward_amount'    => 600.00,                // NIS first-arrival bonus
                'first_arrival_count'            => 10,                    // 10 first-arrival slots
                'seat_capacity'                  => 90,                    // MINIMUM agents to unlock draw (No Max Limit)
                'grand_prize_amount'             => 15000.00,              // NIS lottery prize
                'has_bonus_opportunities'        => false,                 // no bonus opps
                'bonus_per_numbers'              => null,                  // N/A
                'entry_opportunities'            => 1,                     // 1 lottery ticket on entry
                'is_active'                      => true,
                'created_at'                     => now(),
                'updated_at'                     => now(),
            ],

            // ─── Club 2: نادي التفوق ─────────────────────────────────────────────
            [
                'club_id'                        => Str::uuid()->toString(),
                'club_name'                      => 'نادي التفوق',
                'club_order'                     => 2,
                'required_increase'              => 50,                    // new lines to enter
                'required_transfer_count'        => 30,                    // min transfer lines
                'required_transfer_percentage'   => 0.60,                  // 60% rule
                'base_reward_amount'             => 700.00,                // NIS entry bonus
                'first_arrival_reward_amount'    => 1500.00,               // NIS first-arrival bonus
                'first_arrival_count'            => 5,                     // 5 first-arrival slots
                'seat_capacity'                  => 55,                    // MINIMUM agents to unlock draw (No Max Limit)
                'grand_prize_amount'             => 35000.00,              // NIS lottery prize
                'has_bonus_opportunities'        => false,                 // no bonus opps
                'bonus_per_numbers'              => null,                  // N/A
                'entry_opportunities'            => 2,                     // 2 lottery tickets on entry
                'is_active'                      => true,
                'created_at'                     => now(),
                'updated_at'                     => now(),
            ],

            // ─── Club 3: نادي القمة ──────────────────────────────────────────────
            [
                'club_id'                        => Str::uuid()->toString(),
                'club_name'                      => 'نادي القمة',
                'club_order'                     => 3,
                'required_increase'              => 100,                   // new lines to enter
                'required_transfer_count'        => 60,                    // min transfer lines
                'required_transfer_percentage'   => 0.60,                  // 60% rule
                'base_reward_amount'             => 1000.00,               // NIS entry bonus
                'first_arrival_reward_amount'    => 2000.00,               // NIS first-arrival bonus
                'first_arrival_count'            => 5,                     // 5 first-arrival slots
                'seat_capacity'                  => 45,                    // MINIMUM agents to unlock draw (No Max Limit)
                'grand_prize_amount'             => 70000.00,              // NIS lottery prize
                'has_bonus_opportunities'        => true,                  // ✓ Peak Club exclusive
                'bonus_per_numbers'              => 20,                    // 1 bonus ticket per 20 lines
                'entry_opportunities'            => 3,                     // 3 lottery tickets on entry
                'is_active'                      => true,
                'created_at'                     => now(),
                'updated_at'                     => now(),
            ],
        ];

        foreach ($clubs as $club) {
            DB::table('clubs')->updateOrInsert(
                ['club_name' => $club['club_name']], // match on unique name
                $club
            );
        }

        $this->command->info('✅ ClubsSeeder: 3 clubs seeded successfully.');
        $this->command->table(
            ['Club', 'Order', 'Required Lines', 'Prize (NIS)'],
            [
                ['نادي الانطلاق', 1, 25,  '15,000'],
                ['نادي التفوق',   2, 50,  '35,000'],
                ['نادي القمة',    3, 100, '70,000'],
            ]
        );
    }
}

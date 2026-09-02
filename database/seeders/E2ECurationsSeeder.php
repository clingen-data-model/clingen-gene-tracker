<?php

namespace Database\Seeders;

use App\ModeOfInheritance;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class E2ECurationsSeeder extends Seeder
{
    public function run(): void
    {
        $curatorId = User::where('email', 'super.user@example.com')->value('id');
        $moiId = ModeOfInheritance::where('hp_id', 'HP:0000006')->value('id');
        $now = '2026-01-15 12:00:00';
        $symbols = [
            'E2E-ALPHA',
            'E2E-BRAVO',
            'E2E-CHARLIE',
            'E2E-DELTA',
            'E2E-ECHO',
            'E2E-FOXTROT',
            'E2E-GOLF',
            'E2E-HOTEL',
            'E2E-INDIA',
            'E2E-JULIET',
            'E2E-KILO',
            'E2E-LIMA',
        ];

        $curations = [];
        $statuses = [];
        $ownerships = [];

        foreach ($symbols as $index => $symbol) {
            $id = 9101 + $index;
            $expertPanelId = $index % 2 === 0 ? 5 : 6;
            $statusId = $index % 3 === 0 ? 6 : 1;

            $curations[] = [
                'id' => $id,
                'uuid' => sprintf('00000000-0000-4000-8000-%012d', $id),
                'gene_symbol' => $symbol,
                'hgnc_name' => 'Deterministic E2E gene '.$symbol,
                'expert_panel_id' => $expertPanelId,
                'curator_id' => $curatorId,
                'curation_status_id' => $statusId,
                'moi_id' => $moiId,
                'curation_notes' => 'Deterministic Playwright fixture',
                'archived_at' => $index === 0 ? '2026-01-10 12:00:00' : null,
                'archive_reason' => $index === 0 ? 'Deterministic archived E2E fixture' : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $statuses[] = [
                'curation_id' => $id,
                'curation_status_id' => $statusId,
                'status_date' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $ownerships[] = [
                'curation_id' => $id,
                'expert_panel_id' => $expertPanelId,
                'start_date' => '2026-01-15',
                'end_date' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('curations')->insert($curations);
        DB::table('curation_curation_status')->insert($statuses);
        DB::table('curation_expert_panel')->insert($ownerships);
    }
}

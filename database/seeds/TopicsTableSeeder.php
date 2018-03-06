<?php

use App\ExpertPanel;
use App\Topic;
use App\User;
use Illuminate\Database\Seeder;

class TopicsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        $panels = ExpertPanel::all();
        factory(Topic::class, 3)->create()
            ->each(function ($item) use ($panels) {
                $item->update([
                    'expert_panel_id' => $panels->random()->id,
                ]);
            });
        factory(Topic::class, 100)->create()
            ->each(function ($item) use ($users, $panels) {
                $item->update([
                    'expert_panel_id' => $panels->random()->id,
                    'curator_id' => $users->random()->id,
                ]);
            });
        factory(Topic::class, 2)->create();
    }
}

<?php

use App\ExpertPanel;
use Faker\Generator as Faker;

$expertPanels = ExpertPanel::all();

$factory->define(App\Curation::class, function (Faker $faker) use ($expertPanels) {
    return [
        'gene_symbol' => strtoupper($faker->randomLetter.$faker->randomLetter.$faker->randomLetter.$faker->randomLetter.'-'.$faker->randomDigit),
        'expert_panel_id' => ($expertPanels->count() > 0)
                                ? $expertPanels->random()->id
                                : factory(\App\ExpertPanel::class)->create()->id,
        'curator_id' => null,
        'notes' => null
    ];
});

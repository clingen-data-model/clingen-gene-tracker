<?php

use App\Curation;
use App\ExpertPanel;
use Faker\Generator as Faker;
use Ramsey\Uuid\Uuid;

$expertPanels = ExpertPanel::all();

$factory->define(Curation::class, function (Faker $faker) use ($expertPanels) {
    return [
        'gene_symbol' => strtoupper($faker->randomLetter.$faker->randomLetter.$faker->randomLetter.$faker->randomLetter.'-'.$faker->randomDigit),
        'expert_panel_id' => ($expertPanels->count() > 0)
                                ? $expertPanels->random()->id
                                : factory(ExpertPanel::class)->create()->id,
        'curator_id' => null,
        'curation_notes' => null,
        'uuid' => Uuid::uuid4()->toString(),
    ];
});

<?php

use Faker\Generator as Faker;

$factory->define(App\Topic::class, function (Faker $faker) {
    return [
        'gene_symbol' => strtoupper($faker->randomLetter.$faker->randomLetter.$faker->randomLetter.$faker->randomLetter.'-'.$faker->randomDigit),
        'expert_panel_id' => null,
        'curator_id' => null,
        'notes' => null
    ];
});

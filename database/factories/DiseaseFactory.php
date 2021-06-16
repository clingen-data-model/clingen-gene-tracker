<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Disease;
use Faker\Generator as Faker;

$factory->define(Disease::class, function (Faker $faker) {
    return [
        'mondo_id' => 'MONDO:'.$faker->numberBetween(1000000, 9999999),
        'name' => $faker->word,
        'is_obsolete' => $faker->boolean(),
        'replaced_by' => null
    ];
});

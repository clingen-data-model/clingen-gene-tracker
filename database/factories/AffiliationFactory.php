<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Affiliation;
use Faker\Generator as Faker;

$factory->define(Affiliation::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->words(2),
        'short_name' => $faker->unique()->word
    ];
});

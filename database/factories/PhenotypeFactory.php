<?php

use Faker\Generator as Faker;

$factory->define(App\Phenotype::class, function (Faker $faker) {
    return [
        'mim_number' => $faker->unique()->randomNumber(7),
        'name' => $faker->unique()->word
    ];
});

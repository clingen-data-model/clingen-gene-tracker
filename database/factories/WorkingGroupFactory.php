<?php

use Faker\Generator as Faker;

$factory->define(App\WorkingGroup::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->word
    ];
});

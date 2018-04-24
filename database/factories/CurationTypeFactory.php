<?php

use Faker\Generator as Faker;

$factory->define(App\CurationType::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->name
    ];
});

<?php

use Faker\Generator as Faker;

$factory->define(App\Rationale::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->name
    ];
});

<?php

use Faker\Generator as Faker;

$factory->define(\App\ExpertPanel::class, function (Faker $faker) {
    return [
        'name' => $faker->company
    ];
});

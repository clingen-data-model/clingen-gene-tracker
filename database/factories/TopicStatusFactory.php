<?php

use Faker\Generator as Faker;

$factory->define(App\TopicStatus::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->word
    ];
});

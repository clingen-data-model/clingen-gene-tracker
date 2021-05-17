<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\AppState;
use Faker\Generator as Faker;

$factory->define(AppState::class, function (Faker $faker) {
    return [
        'name' => uniqid().'_state',
        'description' => $faker->sentence,
        'default' => null,
        'value' => '0',
        'type' => 'string'
    ];
});

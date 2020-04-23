<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\StateVariable;
use Faker\Generator as Faker;

$factory->define(StateVariable::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->name,
        'type' => $faker->shuffleArray(['string', 'integer', 'float', 'boolean', 'array', 'object']),
        'value' => $faker->randomNumber()
    ];
});

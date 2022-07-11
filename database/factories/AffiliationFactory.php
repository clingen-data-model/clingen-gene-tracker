<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Affiliation;
use Faker\Generator as Faker;

$factory->define(Affiliation::class, function (Faker $faker) {
    return [
        'name' => uniqid(),
        'short_name' => $faker->unique()->word,
        'affiliation_type_id' => config('affiliations.types.gcep'),
    ];
});
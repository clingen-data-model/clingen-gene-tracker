<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Classification;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(Classification::class, function (Faker $faker) {
    $sentence = $faker->sentence;
    return [
        'name' => $sentence,
        'slug' => Str::kebab($sentence)
    ];
});

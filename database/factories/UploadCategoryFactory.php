<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\UploadCategory;
use Faker\Generator as Faker;

$factory->define(UploadCategory::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Gene;
use Faker\Generator as Faker;

$factory->define(Gene::class, function (Faker $faker) {
    return [
        'hgnc_id' => $faker->unique()->randomNumber(6),
        'gene_symbol' => $faker->randomLetter.$faker->randomLetter.$faker->randomLetter.$faker->randomLetter,
        'hgnc_name' => $faker->sentence,
        'hgnc_status' => 'Approved'
    ];
});

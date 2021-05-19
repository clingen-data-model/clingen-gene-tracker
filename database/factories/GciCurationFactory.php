<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\GciCuration;
use Faker\Generator as Faker;

$factory->define(GciCuration::class, function (Faker $faker) {
    return [
        'gdm_uuid' => $faker->uuid,
        'hgnc_id' => DB::table('genes')->select('hgnc_id')->get()->random()->hgnc_id,
        'mondo_id' => $faker->numberBetween(0, 1000000),
        'moi_id' => DB::table('mode_of_inheritances')->select('hp_id')->get()->random()->hp_id,
        'classification_id' => DB::table('classifications')->select('id')->get()->random()->id,
        'status_id' => DB::table('curation_statuses')->select('id')->get()->random()->id,
        'affiliation_id' => DB::table('affiliations')->select('id')->get()->random()->id,
        'creator_uuid' => $faker->uuid,
        'creator_email' => $faker->email
    ];
});

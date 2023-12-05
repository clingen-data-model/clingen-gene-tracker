<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Curation;
use App\Notifications\Curations\PhenotypeAddedForCurationNotification;
use Faker\Generator as Faker;
use Illuminate\Notifications\DatabaseNotification;

$factory->define(DatabaseNotification::class, function (Faker $faker) {
    return [
        'id' => $faker->uuid(),
        'type' => PhenotypeAddedForCurationNotification::class,
        'notifiable_id' => null,
        'notifiable_type' => null,
        'data' => [
            'curation' => factory(Curation::class),
            'phenotype' => factory(Phenotype::class),
        ],
        'read_at' => null,
    ];
});

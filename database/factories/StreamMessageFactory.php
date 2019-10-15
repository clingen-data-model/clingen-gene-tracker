<?php

use Faker\Generator as Faker;

$factory->define(App\StreamMessage::class, function (Faker $faker) {
    $success = (bool)rand(0, 1);
    return [
        'topic' => 'test',
        'message' => $faker->sentence(),
        'sent_at' => $success ? Carbon\Carbon::now() : null,
        'error' => $success ? null : $faker->sentence
    ];
});

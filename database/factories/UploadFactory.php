<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Upload;
use Faker\Generator as Faker;

$factory->define(Upload::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'notes' => null,
        'file_name' => 'dummy.pdf',
        'file_path' => 'public/curator_uploads/'.uniqid().'.pdf',
    ];
});

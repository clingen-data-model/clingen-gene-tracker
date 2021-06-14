<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Carbon\Carbon;
use Faker\Generator as Faker;
use App\IncomingStreamMessage;

$factory->define(IncomingStreamMessage::class, function (Faker $faker) {
    return [
        'topic' => 'gene_validity_events',
        'key' => $faker->uuid,
        'partition' => 0,
        'offset' => $faker->numberBetween(1000, 10000),
        'timestamp' => time(),
        'error_code' => 0,
        'payload' => [
            'date' => Carbon::now()->toIsoString(),
            'status' => [
                'name' => DB::table('curation_statuses')->select('name', 'id')->get()->random()->name,
                'date' => $faker->dateTimeBetween('-10 minutes', 'now'),
            ],
            'report_id' => $faker->uuid,
            "contributors" => [
                [
                    "id" => $faker->uuid,
                    "name" => $faker->name,
                    "email" => $faker->email,
                    "roles" => [
                        "creator"
                    ]
                ]
            ],
            "performed_by" => [
                "id" => $faker->uuid,
                "name" => $faker->name,
                "email" => $faker->email,
                "on_behalf_of" => [
                    "id" => DB::table('affiliations')->select('clingen_id')->get()->random()->clingen_id,
                    "name" => $faker->name
                ]
            ],
            "gene_validity_evidence_level" => [
                "evidence_level" => "Definitive",
                "gene_validity_sop" => "",
                "genetic_condition" => [
                    "gene" => DB::table('genes')->select('hgnc_id')->get()->random()->hgnc_id,
                    "condition" => "MONDO:0019234",
                    "mode_of_inheritance" => DB::table('mode_of_inheritances')->select('hp_id')->get()->random()->hp_id
                ]
            ]

        ]
    ];
});

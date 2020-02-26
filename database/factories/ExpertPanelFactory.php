<?php

use Faker\Generator as Faker;

$groups = \App\WorkingGroup::all();

$factory->define(\App\ExpertPanel::class, function (Faker $faker) use ($groups) {
    return [
        'name' => $faker->unique()->company.' Panel',
        'working_group_id' => ($groups->count() > 0) ? $groups->random()->id : factory(\App\WorkingGroup::class)->create()->id,
        // 'clingen_id' => $faker->unique()->numberBetween(40063, 49999),
        // 'name' => $faker->unique()->company.' Panel',
        // 'parent_id' => ($groups->count() > 0) ? $groups->random()->id : factory(\App\WorkingGroup::class)->create()->id,
        // 'affiliation_type_id' => config('affiliations.types.gcep'),
    ];
});

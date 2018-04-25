<?php

use Backpack\PageManager\app\Models\Page;
use Illuminate\Database\Seeder;

class PagesTableSeeder extends Seeder
{
    public function run()
    {
        Page::create([
            'title'=>'Lumping and Splitting Criteria Overview',
            'template' => 'about',
            'name' => 'criteria-overview',
            'content' => 'Lumping and splitting criteria over view!'
        ]);
    }
}

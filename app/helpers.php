<?php

use Illuminate\Database\Eloquent\Model;

if (!function_exists('site_title')) {
    function site_title()
    {
        $title =  config('app.name', 'Clingen Tracker');
        if (config('app.env') != 'production') {
            $title .= ' - '.strtoupper(config('app.env'));
        }

        return $title;
    }
}

if (! function_exists('seedFromConfig')) {
    function seedFromConfig($config, $modelClass)
    {
        Model::unguard();
        $items = config($config);
        foreach ($items as $slug => $id) {
            $modelClass::updateOrCreate([
              'id'=>$id,
              'slug'=>$slug,
              'name'=>title_case(preg_replace('/-/', ' ', $slug))
            ]);
        }
    }
}

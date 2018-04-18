<?php

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

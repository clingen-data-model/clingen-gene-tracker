<?php

use Illuminate\Database\Eloquent\Model;

if (!function_exists('site_title')) {
    function site_title()
    {
        $title = config('app.name', 'Clingen Tracker');
        if (config('app.env') != 'production') {
            $title .= ' - '.strtoupper(config('app.env'));
        }

        return $title;
    }
}

if (!function_exists('seedFromConfig')) {
    function seedFromConfig($config, $modelClass)
    {
        Model::unguard();
        $items = config($config);
        foreach ($items as $slug => $id) {
            $modelClass::updateOrCreate([
              'id' => $id,
              'slug' => $slug,
              'name' => title_case(preg_replace('/-/', ' ', $slug)),
            ]);
        }
    }
}

if (!function_exists('renderQuery')) {
    function renderQuery($query)
    {
        $treated = preg_replace('/\?/', '"%s"', $query->toSql());

        return call_user_func_array('sprintf', array_merge([$treated], $query->getBindings()));
    }
}

if (!function_exists('forEachFileInDirectory')) {
    function forEachFileInDirectory($directory, $callback)
    {
        $contents = array_filter(scandir($directory), function ($item) {return !in_array($item, ['.', '..']); });
        foreach ($contents as $filename) {
            yield $callback($filename, $directory);
        }
    }
}

if (!function_exists('logDebug')) {
    function logDebug($message, $data = [])
    {
        if (config('app.log_debug', false)) {
            \Log::debug('request: '.spl_object_hash(request()).' - '.microtime(true).' - '.$message, $data);
        }
    }
}

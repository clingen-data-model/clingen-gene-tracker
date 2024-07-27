<?php

use App\Model;
use Illuminate\Support\Str;

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
              'name' => Str::title(preg_replace('/-/', ' ', $slug)),
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
            \Log::debug('request: '.spl_object_hash(request())."\ttime: ".microtime(true)."\t".$message, $data);
        }
    }
}

if (!function_exists('getMaxUploadSize')) {
    function getMaxUploadSize()
    {
        $multipliers = [
            'g' => 1000000,
            'm' => 1000,
            'k' => 1,
        ];

        $iniSize = ini_get('upload_max_filesize');
        $unit = strtolower(substr($iniSize, -1));
        $size = (int) substr($iniSize, 0, strlen($iniSize) - 1);

        return $size * ($multipliers[$unit]);
    }
}

if (!function_exists('getMaxUploadSizeForHumans')) {
    function getMaxUploadSizeForHumans()
    {
        $max = getMaxUploadSize();
        if ($max >= 1000000) {
            return (string) ($max / 1000000).'GB';
        }
        if ($max >= 1000) {
            return (string) ($max / 1000).'MB';
        }

        return (string) $max.'KB';
    }
}

if (!function_exists('getClassesAtPath')) {
    function getClassesAtPath($path): array
    {
        $fqcns = [];
        
        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $phpFiles = new RegexIterator($allFiles, '/\.php$/');
        foreach ($phpFiles as $phpFile) {
            $content = file_get_contents($phpFile->getRealPath());
            $tokens = token_get_all($content);
            $namespace = '';
            for ($index = 0; isset($tokens[$index]); $index++) {
                if (!isset($tokens[$index][0])) {
                    continue;
                }
                if (T_NAMESPACE === $tokens[$index][0]) {
                    $index += 2; // Skip namespace keyword and whitespace
                    while (isset($tokens[$index]) && is_array($tokens[$index])) {
                        $namespace .= $tokens[$index++][1];
                    }
                }
                if (T_CLASS === $tokens[$index][0] && T_WHITESPACE === $tokens[$index + 1][0] && T_STRING === $tokens[$index + 2][0]) {
                    $index += 2; // Skip class keyword and whitespace
                    $fqcns[] = $namespace.'\\'.$tokens[$index][1];
        
                    # break if you have one class per file (psr-4 compliant)
                    # otherwise you'll need to handle class constants (Foo::class)
                    break;
                }
            }
        }   
        return $fqcns;
    }
}
<?php

declare(strict_types=1);

$publicPath = realpath(dirname(__DIR__).'/public');
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$requestedFile = realpath($publicPath.$requestPath);

if (
    $requestPath !== '/'
    && $requestedFile !== false
    && str_starts_with($requestedFile, $publicPath.DIRECTORY_SEPARATOR)
    && is_file($requestedFile)
) {
    return false;
}

require $publicPath.'/index.php';

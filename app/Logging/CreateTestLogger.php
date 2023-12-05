<?php

namespace App\Logging;

use Monolog\Handler\TestHandler;
use Monolog\Logger;

class CreateTestLogger
{
    /**
     * Create a custom Monolog instance
     */
    public function __invoke(array $config): Logger
    {
        $monolog = new Logger('test');
        $monolog->pushHandler(new TestHandler());

        return $monolog;
    }
}

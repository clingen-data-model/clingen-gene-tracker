<?php

namespace App\Logging;

use Monolog\Handler\TestHandler;
use Monolog\Logger;

class CreateTestLogger
{
    /**
     * Create a custom Monolog instance
     *
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $monolog = new Logger('test');
        $monolog->pushHandler(new TestHandler());

        return $monolog;
    }
}

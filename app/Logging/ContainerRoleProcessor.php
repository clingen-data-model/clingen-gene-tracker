<?php

namespace App\Logging;

use Monolog\LogRecord;

class ContainerRoleProcessor
{
    public function __invoke(LogRecord $record)
    {
        if (config('app.container_role')) {
            $record['channel'] = $record['channel'].':'.config('app.container_role');
        }

        return $record;
    }
}

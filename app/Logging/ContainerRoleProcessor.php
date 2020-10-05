<?php

namespace App\Logging;

class ContainerRoleProcessor
{
    public function __invoke(array $record)
    {
        if (config('app.container_role')) {
            $record['channel'] = $record['channel'].':'.config('app.container_role');
        }

        return $record;
    }
}

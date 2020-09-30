<?php

namespace Profiling;

class TaskTimeWriter
{
    private $timer;

    public function __construct(TaskTimeSingleton $timer)
    {
        $this->timer = $timer;
    }

    public function writeToFile($filename = null)
    {
        $writeFileName = '/srv/app/'.($filename ?? 'timing-'.date('Y-m-d_H:i:s').'.log');

        $entries = array_map(function ($evt) {
            return date('Y-m-d H:i:s', $evt->getMicroTime())."\t".$evt->getMicrotime()."\t".$evt->getName();
        }, $this->timer->getEvents());

        $eventsString = implode("\n", $entries);

        file_put_contents($writeFileName, $eventsString);
    }
}

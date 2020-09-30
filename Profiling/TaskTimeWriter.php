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
        $writeFileName = '/srv/app/public/profile_logs/'.date('Y-m-d_H:i:s', $this->timer->getStartTime()).($filename ?? $this->formatRequestUri($_SERVER['REQUEST_URI']).'.log');

        // $entries = array_map(function ($evt) {
        //     return date('Y-m-d H:i:s', $evt->getMicroTime())."\t".$evt->getMicrotime()."\t".$evt->getName();
        // }, $this->timer->getEvents());

        $entries = [];
        $events = $this->timer->getEvents();
        for ($i = 0; $i < count($events); ++$i) {
            $evt = $events[$i];
            $prevIdx = $i == 0 ? 0 : $i - 1;
            $lastMicrotime = $events[$prevIdx]->getMicrotime();
            $duration = round($evt->getMicrotime() - $lastMicrotime, 4);
            // $entry = array_merge($evt->toArray(), ['duration' => $duration]);
            $entries[] = date('Y-m-d H:i:s', $evt->getMicroTime())."\t".$evt->getMicrotime()."\t".$duration."\t".$evt->getName();
        }

        $eventsString = implode("\n", $entries);
        $totalTime = ($this->timer->getEndTime() - $this->timer->getStartTime());
        $uri = $_SERVER['REQUEST_URI'];

        file_put_contents($writeFileName, $uri."\n\n".$eventsString."\n\nTotal time: ".round($totalTime, 3));
    }

    private function formatRequestUri($uri)
    {
        return urlencode(preg_replace('/\//', '_', $uri));
    }
}

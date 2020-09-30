<?php

namespace Profiling;

class TaskTimingEvent
{
    private $name;
    private $microtime;

    public function __construct($name, $microtime = null)
    {
        $this->name = $name;
        $this->microtime = ($microtime) ? $microtime : microtime(true);
    }

    public function getname()
    {
        return $this->name;
    }

    public function getMicrotime()
    {
        return $this->microtime;
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'microtime' => $this->microtime,
        ];
    }
}

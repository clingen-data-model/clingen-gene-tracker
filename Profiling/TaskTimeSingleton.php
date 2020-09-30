<?php

namespace Profiling;

use Exception;

require_once __DIR__.'/TaskTimingEvent.php';

class TaskTimeSingleton
{
    private static $instance;
    private $events;

    public static function init(): TaskTimeSingleton
    {
        if (static::$instance) {
            return static::$instance;
        }

        return static::$instance = new self([]);
    }

    public function __construct($events = [])
    {
        $this->events = $events;
    }

    public function addEvent($name, $microtime = null)
    {
        $event = new TaskTimingEvent($name, $microtime);
        array_push($this->events, $event);

        return $this;
    }

    public function getEvents()
    {
        return $this->events;
    }

    public function iterEvents()
    {
        foreach ($this->events as $event) {
            yield $event;
        }
    }

    public function toArray()
    {
        return array_map(function ($evt) {
            return $evt->toArray();
        }, $this->events);
    }

    public function getStartTime()
    {
        if (count($this->events) == 0) {
            throw new Exception('No events in TaskTimeSingleton instance.');
        }

        return $this->events[0]->getMicrotime();
    }

    public function getEndTime()
    {
        $eventCount = count($this->events);
        if ($eventCount == 0) {
            throw new Exception('No events in TaskTimeSingleton instance.');
        }

        return $this->events[$eventCount - 1]->getMicrotime();
    }
}

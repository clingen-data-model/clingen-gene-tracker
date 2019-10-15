<?php

namespace App\Contracts;

interface MessagePusher
{
    public function topic(string $topic);
    public function push(string $message);
}

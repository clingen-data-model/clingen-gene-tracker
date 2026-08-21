<?php

namespace App;

use App\Model;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{

    public function getReadableTypeAttribute()
    {
        $parts = explode('\\', $this->type);
        return $parts[count($parts)-1];
    }
}

<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    use CrudTrait;

    public function getReadableTypeAttribute()
    {
        $parts = explode('\\', $this->type);
        return $parts[count($parts)-1];
    }
}

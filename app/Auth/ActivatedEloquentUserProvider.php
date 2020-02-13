<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\UserProvider;

class ActivatedEloquentUserProvider extends EloquentUserProvider implements UserProvider
{
    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)->whereNull('deactivated_at');
    }

}
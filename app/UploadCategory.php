<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;

class UploadCategory extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
    ];
}

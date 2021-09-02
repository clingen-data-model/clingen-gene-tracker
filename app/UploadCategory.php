<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class UploadCategory extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
    ];
}

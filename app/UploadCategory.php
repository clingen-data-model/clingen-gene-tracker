<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Model;

class UploadCategory extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
    ];
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\CrudTrait;
use Venturecraft\Revisionable\RevisionableTrait;

class ExpertPanel extends Model
{
    use RevisionableTrait, CrudTrait;

    protected $fillable = ['name'];
}

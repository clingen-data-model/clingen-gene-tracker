<?php

namespace App;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;

class ApiClient extends Model implements HasApiTokensContract
{
    use HasFactory;
    use HasApiTokens;
    use Notifiable;
    use CrudTrait;

    public $fillable = [
        'name',
        'contact_email',
        'uuid',
    ];
}

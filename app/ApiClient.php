<?php

namespace App;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;

class ApiClient extends Model implements HasApiTokensContract
{
    use HasFactory;
    use HasApiTokens;
    use Notifiable;

    public $fillable = [
        'name',
        'contact_email',
        'uuid'
    ];
}

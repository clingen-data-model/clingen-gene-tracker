<?php

namespace App;

use App\Events\User\Created;
use Backpack\Base\app\Notifications\ResetPasswordNotification as ResetPasswordNotification;
use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Venturecraft\Revisionable\RevisionableTrait;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, RevisionableTrait, CrudTrait, SoftDeletes, HasRoles;

    protected $revisionCreationsEnabled = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','deactivated_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dispatchesEvents = [
        'created' => Created::class
    ];

    public static function boot()
    {
        static::creating(function ($model) {
            if (is_null($model->password)) {
                if (env('production')) {
                    $model->password = uniqid();
                } else {
                    $model->password = 'tester';
                }
            }
        });
    }

    public function topics()
    {
        return $this->hasMany(Topic::class, 'curator_id');
    }

    public function expertPanels()
    {
        return $this->belongsToMany(ExpertPanel::class)->withTimestamps();
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function deactivateUser($crud = false)
    {
        return '<a class="btn btn-xs btn-default" href="'.\Request::root().'/admin/user/'.$crud->id.'/deactivate" data-toggle="tooltip" title="Deactivate this user." onClick="return confirm(\'Are you sure?\');"><i class="fa fa-ban"></i> Deactviate</a>';
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function inExpertPanel($panel)
    {
        if (is_int($panel)) {
            return $this->expertPanels->contains(function ($ep) use ($panel) {
                return $ep->id == $panel;
            });
        }
        if (is_object($panel) && get_class($panel) == ExpertPanel::class) {
            return $this->expertPanels->contains(function ($ep) use ($paen) {
                return $ep->id = $panel->id;
            });
        }
    }
}

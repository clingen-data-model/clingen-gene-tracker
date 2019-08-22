<?php

namespace App;

use App\Events\User\Created;
use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Venturecraft\Revisionable\RevisionableTrait;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, RevisionableTrait, CrudTrait, SoftDeletes, HasRoles, Impersonate;

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

    protected $dates = [
        'deactivated_at'
    ];

    public static function boot()
    {
        parent::boot();
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

    public function curations()
    {
        return $this->hasMany(Curation::class, 'curator_id');
    }

    public function expertPanels()
    {
        return $this->belongsToMany(ExpertPanel::class)
                ->withPivot('can_edit_curations', 'is_curator', 'is_coordinator')
                ->withTimestamps();
    }
    
    public function deactivateUser($crud = false)
    {
        if (is_null($this->deactivated_at)) {
            return '<a class="btn btn-xs btn-default" href="'.\Request::root().'/admin/user/'.$this->id.'/deactivate" data-toggle="tooltip" title="Deactivate this user." onClick="return confirm(\'Are you sure?\');"><i class="fa fa-ban"></i> Deactviate</a>';
        }
    }

    public function setPasswordAttribute($value)
    {
        if (Hash::needsRehash($value)) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
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

    public function canEditPanelCurations($panel)
    {
        return $this->expertPanels->contains(function ($value, $key) use ($panel) {
            return $value->id == $panel->id && ((boolean)$value->pivot->can_edit_curations || (boolean)$value->pivot->is_coordinator);
        });
    }

    public function isPanelCoordinator($panel)
    {
        return $this->expertPanels->contains(function ($value, $key) use ($panel) {
            return $value->id == $panel->id && (boolean)$value->pivot->is_coordinator;
        });
    }

    public function isPanelCurator($panel)
    {
        return $this->expertPanels->contains(function ($value, $key) use ($panel) {
            return $value->id == $panel->id && (boolean)$value->pivot->is_curator;
        });
    }

    public function isCoordinator()
    {
        return $this->expertPanels->contains(function ($value, $key) {
            return $value->pivot->is_coordinator;
        });
    }

    public function canImpersonate()
    {
        return $this->hasRole('programmer|admin');
    }

    public function canBeImpersonated()
    {
        if (\Auth::user()->hasRole('admin')) {
            return !$this->hasRole("programmer|admin");
        }

        return !$this->hasRole("programmer");
    }

    public function getPanelsCoordinating()
    {
        if ($this->hasAnyRole('admin|programmer')) {
            return ExpertPanel::all();
        }
        return $this->expertPanels->where('pivot.is_coordinator', 1);
    }

    public function hasPermissionTo($permString)
    {
        return $this->getAllPermissions()->contains('name', $permString);
    }
   
    public function getAllPermissions() 
    {
        if (is_null($this->allPermissions)) {
            $permissions = $this->permissions;

            if ($this->roles) {
                $permissions = $permissions->merge($this->getPermissionsViaRoles());
            }

            $this->allPermissions = $permissions->sort()->values();
        }
        
        return $this->allPermissions;
    }

    
}

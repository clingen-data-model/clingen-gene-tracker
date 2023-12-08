<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Events\User\Created;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
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
    use HasApiTokens;
    use Notifiable;
    use RevisionableTrait;
    use CrudTrait;
    use SoftDeletes;
    use HasRoles;
    use Impersonate;

    protected $revisionCreationsEnabled = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'deactivated_at',
        'gci_uuid',
        'affiliation_id',
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
        'created' => Created::class,
    ];

    protected $casts = [
        'deactivated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(
            function ($model) {
                if (is_null($model->password)) {
                    $model->password = 'tester';
                    if (config('settings.production')) {
                        $model->password = uniqid();
                    }
                }
            }
        );
    }

    public function curations()
    {
        return $this->hasMany(Curation::class, 'curator_id');
    }

    public function affiliations()
    {
        return $this->belongsToMany(Affiliation::class);
    }

    public function expertPanels()
    {
        return $this->belongsToMany(ExpertPanel::class)
            ->withPivot('can_edit_curations', 'is_curator', 'is_coordinator')
            ->withTimestamps();
    }

    public function coordinatorPanels()
    {
        return $this->expertPanels()->where('expert_panel_user.is_coordinator', 1);
    }

    public function editorPanels()
    {
        return $this->expertPanels()->where('expert_panel_user.can_edit_curations', 1);
    }

    public function coordinatorOrEditorPanels()
    {
        return $this->expertPanels()->where(
            function ($query) {
                $query->orWhere('expert_panel_user.is_coordinator', 1)
                    ->orWhere('expert_panel_user.can_edit_curations', 1);
            }
        );
    }

    public function deactivateUser()
    {
        if (is_null($this->deactivated_at)) {
            return '<a class="btn btn-xs btn-default" '
                .'href="'.Request::root().'/admin/user/'.$this->id.'/deactivate" '
                .'data-toggle="tooltip" '
                .'title="Deactivate this user" '
                .'onClick="return confirm(\'Are you sure?\');">'
                .'<i class="fa fa-ban"></i> Deactviate</a>';
        }
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value;
        if (Hash::needsRehash($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    public function inExpertPanel($panel)
    {
        if (is_int($panel)) {
            return $this->expertPanels->contains(function ($ep) use ($panel) {
                return $ep->id == $panel;
            });
        }
        if (is_object($panel) && $panel::class == ExpertPanel::class) {
            return $this->expertPanels->contains(function ($ep) use ($panel) {
                return $ep->id = $panel->id;
            });
        }
    }

    public function canEditPanelCurations($panel)
    {
        return $this->expertPanels->contains(function ($value, $key) use ($panel) {
            return $value->id == $panel->id
                && ((bool) $value->pivot->can_edit_curations || (bool) $value->pivot->is_coordinator);
        });
    }

    public function isPanelCoordinator($panel)
    {
        return $this->expertPanels->contains(function ($value, $key) use ($panel) {
            return $value->id == $panel->id && (bool) $value->pivot->is_coordinator;
        });
    }

    public function isPanelCurator($panel)
    {
        return $this->expertPanels->contains(function ($value, $key) use ($panel) {
            return $value->id == $panel->id && (bool) $value->pivot->is_curator;
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
        if (Auth::user()->hasRole('admin')) {
            return ! $this->hasRole('programmer|admin');
        }

        return ! $this->hasRole('programmer');
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

    public function routeNotificationForSlack()
    {
        return config('logging.channels.slack.url');
    }

    /**
     * SCOPES
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deactivated_at');
    }

    public function scopeDeactivated($query)
    {
        return $query->whereNotNull('deactivate_at');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Affiliation;
use App\ExpertPanel;
use App\Http\Requests\UserRequest as StoreRequest;
use App\Http\Requests\UserRequest as UpdateRequest;
use App\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserCrudController extends CrudController
{    
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as parentStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as parentUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\ReviseOperation\ReviseOperation;

    protected $user = null;

    public function setUp(): void
    {
        $this->user = Auth::user();

        // defining this up front so it can be a column and a field (is there a better way to do this?)
        $roleInfo = [
            'label' => 'Roles',
            'type' => 'select2_multiple',
            'name' => 'roles',
            'entity' => 'roles',
            'attribute' => 'name',
            'model' => Role::class,
            'pivot' => true,
        ];

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel(\App\User::class);
        $this->crud->setRoute(config('backpack.base.route_prefix').'/user');
        $this->crud->setEntityNameStrings('user', 'users');

        $this->crud->setFromDb();
        $this->crud->removeColumn('gci_uuid'); // always null anyway?
        $this->crud->addColumn($roleInfo);

        // ------ CRUD FIELDS

        $this->crud->addField([
            'label' => 'Expert Panels',
            'type' => 'expert_panel_field',
            'name' => 'expertPanels',
            'entity' => 'expertPanels',
            'attribute' => 'name',
            'model' => ExpertPanel::class,
            'pivot' => true,
        ]);

        $this->crud->addField($roleInfo);

        $this->crud->addField([
            'label' => 'Extra Permissions',
            'type' => 'select2_multiple',
            'name' => 'permissions',
            'entity' => 'permissions',
            'attribute' => 'name',
            'model' => Permission::class,
            'pivot' => true,
        ]);

        $this->crud->removeField('deactivated_at');
        $this->crud->removeField('password');

        // ------ CRUD BUTTONS
        $this->crud->addButtonFromView('line', 'deactivate', 'deactivate', 'end');
        // ------ CRUD ACCESS
        $this->crud->denyAccess(['list', 'create', 'update', 'deactivate', 'delete']);
        if ($this->user->hasPermissionTo('list users')) {
            $this->crud->allowAccess(['list']);
        }
        if ($this->user->hasPermissionTo('create users')) {
            $this->crud->allowAccess(['create']);
        }
        if ($this->user->hasPermissionTo('update users')) {
            $this->crud->allowAccess(['update']);
        }
        if ($this->user->hasPermissionTo('deactivate users')) {
            $this->crud->allowAccess(['deactivate']);
        }
        if ($this->user->hasPermissionTo('delete users')) {
            $this->crud->allowAccess(['delete']);
        }
    }

    public function store(StoreRequest $request)
    {
        // your additional operations before save here
        $redirect_location = $this->parentStore($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry

        $this->processExpertPanels($request);

        return $redirect_location;
    }

    public function update(UpdateRequest $request)
    {
        // do not update password if left blank
        if ($request['password'] === null) {
            unset($request['password']);
        }
        $redirect_location = $this->parentUpdate($request);
        $this->processExpertPanels($request);

        return $redirect_location;
    }

    public function deactivate(Request $request)
    {
        if ($this->user->hasPermissionTo('deactivate users')) {
            $user = User::findOrFail($request->id);
            $user->update([
                'deactivated_at' => Carbon::now(),
            ]);

            \Alert::add('success', 'User '.$user->name.' deactivated successfully')->flash();
            return Redirect::back();
        }

        \Alert::add('error', 'Logged in user does not hae access to do deactivate users')->flash();
        return Redirect::back();
    }

    public function reactivate(Request $request)
    {
        if ($this->user->hasPermissionTo('deactivate users')) {
            $user = User::findOrFail($request->id);
            $user->update([
                'deactivated_at' => null,
            ]);

            \Alert::add('success', 'User '.$user->name.' reactivated successfully')->flash();
            return Redirect::back();
        }

        \Alert::add('error', 'Logged in user does not hae access to do reactivate users')->flash();
        return Redirect::back();
    }

    private function processExpertPanels(Request $request)
    {
        if ($request->expert_panels_json) {
            $expertPanels = [];
            foreach (json_decode($request->expert_panels_json) as $panel) {
                if (!isset($panel->id)) {
                    continue;
                }
                unset($panel->pivot->created_at);
                unset($panel->pivot->updated_at);
                $expertPanels[$panel->id] = (array) $panel->pivot;
            }
            $this->crud->entry->expertPanels()->sync($expertPanels);
            // dd($this->crud->entry->expertPanels->pluck('pivot')->map(fn ($i) => $i->toArray()));
        }
    }
}

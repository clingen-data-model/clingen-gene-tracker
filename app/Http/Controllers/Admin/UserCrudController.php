<?php

namespace App\Http\Controllers\Admin;

use App\ExpertPanel;
use App\Http\Requests\UserRequest as StoreRequest;
use App\Http\Requests\UserRequest as UpdateRequest;
use App\Curation;
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
    protected $user = null;

    public function setUp(): void
    {
        $this->user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel(\App\User::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/user');
        $this->crud->setEntityNameStrings('user', 'users');

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */

        $this->crud->setFromDb();

        // ------ CRUD FIELDS

        // $this->crud->addField([
        //     'label' => 'Expert Panels',
        //     'type' => 'expert_panel_field',
        //     'name' => 'expertPanels',
        //     'entity' => 'expertPanels',
        //     'attribute' => 'name',
        //     'model' => ExpertPanel::class,
        //     'pivot' => true
        // ], 'both');

        // $this->crud->addField([
        //     'label' => 'Roles',
        //     'type' => 'select2_multiple',
        //     'name' => 'roles',
        //     'entity' => 'roles',
        //     'attribute' => 'name',
        //     'model' => Role::class,
        //     'pivot' => true
        // ], 'both');

        $this->crud->removeField('deactivated_at');
        $this->crud->removeField('password');
        
        // ------ CRUD BUTTONS
        $this->crud->addButtonFromView('line', 'deactivate', 'deactivate', 'end');
        // ------ CRUD ACCESS
        $this->crud->denyAccess(['list','create','update','deactivate','delete']);
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
        $this->crud->allowAccess('revisions');

    }

    public function store(StoreRequest $request)
    {
        // your additional operations before save here
        $redirect_location = parent::storeCrud($request);
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
        // your additional operations before save here
        $redirect_location = parent::updateCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        $this->processExpertPanels($request);
        return $redirect_location;
    }

    public function deactivate(Request $request)
    {
        if ($this->user->hasPermissionTo('deactivate users')) {
            $user = User::findOrFail($request->id);
            $user->update([
                'deactivated_at'=>Carbon::now()
            ]);

            return Redirect::back()->with(['msg','User '.$user->name.' deactivated successfully']);
        }

        return Redirect::back()->withErrors(['msg','Logged in user does not hae access to do deactivate users']);
    }

    public function reactivate(Request $request)
    {
        if ($this->user->hasPermissionTo('deactivate users')) {
            $user = User::findOrFail($request->id);
            $user->update([
                'deactivated_at'=>null
            ]);

            return Redirect::back()->with(['msg','User '.$user->name.' reactivated successfully']);
        }

        return Redirect::back()->withErrors(['msg','Logged in user does not hae access to do deactivate users']);
    }

    private function processExpertPanels(Request $request)
    {
        if ($request->expert_panels_json) {
            $expertPanels = [];
            foreach (json_decode($request->expert_panels_json) as $panel) {
                if (!isset($panel->id)) {
                    continue;
                }
                $expertPanels[$panel->id] = (array)$panel->pivot;
            }
            $this->crud->entry->expertPanels()->sync($expertPanels);
        }
    }
}

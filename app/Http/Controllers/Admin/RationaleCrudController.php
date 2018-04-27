<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RationaleRequest as StoreRequest;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\RationaleRequest as UpdateRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;

class RationaleCrudController extends CrudController
{
    public function setup()
    {

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel(\App\Rationale::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/rationale');
        $this->crud->setEntityNameStrings('rationale', 'rationales');

        $this->crud->denyAccess(['list','create','update','deactivate','delete']);
        if (\Auth::user()->hasPermissionTo('list users')) {
            $this->crud->allowAccess(['list']);
        }
        if (\Auth::user()->hasPermissionTo('create users')) {
            $this->crud->allowAccess(['create']);
        }
        if (\Auth::user()->hasPermissionTo('update users')) {
            $this->crud->allowAccess(['update']);
        }
        if (\Auth::user()->hasPermissionTo('deactivate users')) {
            $this->crud->allowAccess(['deactivate']);
        }
        if (\Auth::user()->hasPermissionTo('delete users')) {
            $this->crud->allowAccess(['delete']);
        }

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */

        $this->crud->setFromDb();
    }

    public function store(StoreRequest $request)
    {
        // your additional operations before save here
        $redirect_location = parent::storeCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }

    public function update(UpdateRequest $request)
    {
        // your additional operations before save here
        $redirect_location = parent::updateCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }
}

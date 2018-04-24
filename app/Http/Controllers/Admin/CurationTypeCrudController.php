<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CurationTypeRequest as StoreRequest;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\CurationTypeRequest as UpdateRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;

class CurationTypeCrudController extends CrudController
{
    public function setup()
    {

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\CurationType');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/curation-type');
        $this->crud->setEntityNameStrings('curation type', 'curation types');

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */

        $this->crud->setFromDb();

        $this->crud->denyAccess(['list','create','update','delete']);
        if (\Auth::user()->hasPermissionTo('create curation-types')) {
            $this->crud->allowAccess(['list']);
        }

        if (\Auth::user()->hasPermissionTo('create curation-types')) {
            $this->crud->allowAccess(['create']);
        } else {
            $this->crud->removeButton('add');
        }

        if (\Auth::user()->hasPermissionTo('update curation-types')) {
            $this->crud->allowAccess(['update']);
        } else {
            $this->crud->removeButton('edit');
        }

        if (\Auth::user()->hasPermissionTo('delete curation-types')) {
            $this->crud->allowAccess(['delete']);
        } else {
            $this->crud->removeButton('delete');
        }
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RationaleRequest as StoreRequest;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\RationaleRequest as UpdateRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;

class RationaleCrudController extends CrudController
{    
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setUp(): void
    {

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel(\App\Rationale::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/rationale');
        $this->crud->setEntityNameStrings('rationale', 'rationales');

        $this->crud->denyAccess(['list','create','update','delete']);
        if (\Auth::user()->hasPermissionTo('list users')) {
            $this->crud->allowAccess(['list']);
        }
        if (\Auth::user()->hasPermissionTo('create users')) {
            $this->crud->allowAccess(['create']);
        }
        if (\Auth::user()->hasPermissionTo('update users')) {
            $this->crud->allowAccess(['update']);
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

    protected function setupCreateOperation()
    {
        $this->crud->setValidation(StoreRequest::class);
    }

    protected function setupUpdateOperation()
    {
        $this->crud->setValidation(UpdateRequest::class);
    }

}

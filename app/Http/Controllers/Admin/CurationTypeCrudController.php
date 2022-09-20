<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CurationTypeRequest as StoreRequest;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\CurationTypeRequest as UpdateRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;

class CurationTypeCrudController extends CrudController
{    
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function setUp(): void
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

    protected function setupCreateOperation()
    {
        $this->crud->setValidation(StoreRequest::class);
    }

    protected function setupUpdateOperation()
    {
        $this->crud->setValidation(UpdateRequest::class);
    }
}

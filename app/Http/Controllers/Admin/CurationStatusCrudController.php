<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CurationStatusRequest as StoreRequest;
use App\Http\Requests\CurationStatusRequest as UpdateRequest;
use App\CurationStatus;
use Backpack\CRUD\app\Http\Controllers\CrudController;

class CurationStatusCrudController extends CrudController
{    
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setUp(): void
    {
        $this->user = \Auth::user();

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel(CurationStatus::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/curation-status');
        $this->crud->setEntityNameStrings('curation status', 'curation statuses');

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */

        $this->crud->setFromDb();

        $this->crud->denyAccess(['list','create','update','delete']);
        if ($this->user->hasPermissionTo('create curation-statuses')) {
            $this->crud->allowAccess(['list']);
        }

        if ($this->user->hasPermissionTo('create curation-statuses')) {
            $this->crud->allowAccess(['create']);
        } else {
            $this->crud->removeButton('add');
        }

        if ($this->user->hasPermissionTo('update curation-statuses')) {
            $this->crud->allowAccess(['update']);
        } else {
            $this->crud->removeButton('edit');
        }

        if ($this->user->hasPermissionTo('delete curation-statuses')) {
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

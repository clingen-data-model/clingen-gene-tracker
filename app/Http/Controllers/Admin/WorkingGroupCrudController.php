<?php

namespace App\Http\Controllers\Admin;

use App\Affiliation;
use App\WorkingGroup;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use App\Http\Requests\WorkingGroupRequest as StoreRequest;
use App\Http\Requests\WorkingGroupRequest as UpdateRequest;

class WorkingGroupCrudController extends CrudController
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
        $this->crud->setModel(WorkingGroup::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/working-group');
        $this->crud->setEntityNameStrings('working group', 'working groups');

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */

        $this->crud->setFromDb();
        
        // ------ CRUD ACCESS
        $this->crud->denyAccess(['list', 'create', 'update', 'reorder', 'delete']);
        if (\Auth::user()->hasPermissionTo('list working-groups')) {
            $this->crud->allowAccess('list');
        }
        if (\Auth::user()->hasPermissionTo('create working-groups')) {
            $this->crud->allowAccess('create');
        }

        if (\Auth::user()->hasPermissionTo('update working-groups')) {
            $this->crud->allowAccess('update');
        }

        if (\Auth::user()->hasPermissionTo('delete working-groups')) {
            $this->crud->allowAccess('delete');
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

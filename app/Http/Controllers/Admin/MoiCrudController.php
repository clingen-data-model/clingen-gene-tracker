<?php

namespace App\Http\Controllers\Admin;

use App\ModeOfInheritance;

// VALIDATION: change the requests to match your own file names if you need form validation
use Backpack\CRUD\CrudPanel;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\MoiAdminRequest as StoreRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use App\Http\Requests\MoiAdminRequest as UpdateRequest;

/**
 * Class MoiCrudControllerCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class MoiCrudController extends CrudController
{    
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel(ModeOfInheritance::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/moi');
        $this->crud->setEntityNameStrings('MOI', 'MOIs');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        // $this->crud->setFromDb();
       
        $this->crud->addColumns([
            [
                'name' => 'name',
                'type' => 'text'
            ],
            [
                'name' => 'abbreviation',
                'type' => 'text'
            ],
            [
                'name' => 'parent_id',
                'type' => 'select',
                'entity' => 'parent',
                'attribute' => 'name',
                'model' => ModeOfInheritance::class
            ],
            [
                'name' => 'curatable',
                'type' => 'boolean'
            ]
        ]);

        $this->crud->addFields([
            [
                'name' => 'name',
                'label' => 'Name',
                'type' => 'text',
                'attributes' => [
                    'disabled' => true
                ]
            ],
            [
                'name' => 'abbreviation',
                'label' => 'Abbreviation',
                'type' => 'text',
                'attributes' => [
                    'disabled' => true
                ]
            ],
            [
                'name' => 'curatable',
                'label' => 'Curatable',
                'type' => 'boolean'
            ]
        ]);

        // add asterisk for fields that are required in MoiCrudControllerRequest
        $this->crud->setRequiredFields(StoreRequest::class, 'create');
        $this->crud->setRequiredFields(UpdateRequest::class, 'edit');

        // ------ CRUD ACCESS
        $this->crud->denyAccess(['list','create','update','deactivate','delete']);
        if (Auth::user()->hasPermissionTo('list mois')) {
            $this->crud->allowAccess(['list']);
        }
        if (Auth::user()->hasPermissionTo('create mois')) {
            $this->crud->allowAccess(['create']);
        }
        if (Auth::user()->hasPermissionTo('update mois')) {
            $this->crud->allowAccess(['update']);
        }
        if (Auth::user()->hasPermissionTo('delete mois')) {
            $this->crud->allowAccess(['delete']);
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

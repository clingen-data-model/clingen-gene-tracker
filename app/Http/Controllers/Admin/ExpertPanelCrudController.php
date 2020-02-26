<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ExpertPanelRequest as StoreRequest;
use App\Http\Requests\ExpertPanelRequest as UpdateRequest;
use App\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Support\Facades\Auth;

class ExpertPanelCrudController extends CrudController
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
        $this->crud->setModel(\App\ExpertPanel::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/expert-panel');
        $this->crud->setEntityNameStrings('expert-panel', 'expert-panels');

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */

        $this->crud->setFromDb();
        $this->crud->removeField('working_group_id', 'both');

        $this->crud->addField([
            'label' => 'Users',
            'type' => 'select2_multiple',
            'name' => 'users',
            'entity' => 'users',
            'attribute' => 'name',
            'model' => User::class,
            'pivot' => true
        ], 'both');

        $this->crud->addField([
            'name' => 'working_group_id',
            'label' => 'Working Group',
            'entity' => 'workingGroup',
            'model' => \App\WorkingGroup::class,
            'type' => 'select2',
            'attribute' => 'name',
        ]);

        // ------ COLUMNS

        $this->crud->addColumn([
            'name' => 'id',
            'label' => 'ID'
        ])->makeFirstColumn();
        $this->crud->setColumnDetails('working_group_id', [
           'label' => "Working Group", // Table column heading
           'type' => "select",
           'name' => 'working_group_id', // the column that contains the ID of that connected entity;
           'entity' => 'workingGroup', // the method that defines the relationship in your Model
           'attribute' => "name", // foreign key attribute that is shown to user
           'model' => \App\WorkingGroup::class
        ]);

        // ------ CRUD ACCESS
        $this->crud->denyAccess(['list','create','update','deactivate','delete']);
        if ($this->user->hasPermissionTo('list expert-panels')) {
            $this->crud->allowAccess(['list']);
        }
        if ($this->user->hasPermissionTo('create expert-panels')) {
            $this->crud->allowAccess(['create']);
        }
        if ($this->user->hasPermissionTo('update expert-panels')) {
            $this->crud->allowAccess(['update']);
        }
        if ($this->user->hasPermissionTo('update delete')) {
            $this->crud->allowAccess(['delete']);
        }

        // ------ REVISIONS
        $this->crud->allowAccess('revisions');
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

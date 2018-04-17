<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\TopicStatusRequest as StoreRequest;
use App\Http\Requests\TopicStatusRequest as UpdateRequest;
use App\TopicStatus;
use Backpack\CRUD\app\Http\Controllers\CrudController;

class TopicStatusCrudController extends CrudController
{
    public function setup()
    {
        $this->user = \Auth::user();

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel(TopicStatus::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/topic-status');
        $this->crud->setEntityNameStrings('topic status', 'topic statuses');

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */

        $this->crud->setFromDb();

        $this->crud->denyAccess(['list','create','update','delete']);
        if ($this->user->hasPermissionTo('create topic-statuses')) {
            $this->crud->allowAccess(['list']);
        }

        if ($this->user->hasPermissionTo('create topic-statuses')) {
            $this->crud->allowAccess(['create']);
        } else {
            $this->crud->removeButton('add');
        }

        if ($this->user->hasPermissionTo('update topic-statuses')) {
            $this->crud->allowAccess(['update']);
        } else {
            $this->crud->removeButton('edit');
        }

        if ($this->user->hasPermissionTo('delete topic-statuses')) {
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

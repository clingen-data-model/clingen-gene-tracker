<?php

namespace App\Http\Controllers\Admin;

use App\Affiliation;
use App\WorkingGroup;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use App\Http\Requests\WorkingGroupRequest as StoreRequest;
use App\Http\Requests\WorkingGroupRequest as UpdateRequest;

class WorkingGroupCrudController extends CrudController
{
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

        // ------ CRUD REORDER
        // $this->crud->enableReorder('label_name', MAX_TREE_LEVEL);
        // NOTE: you also need to do allow access to the right users: $this->crud->allowAccess('reorder');

        // ------ CRUD DETAILS ROW
        // $this->crud->enableDetailsRow();
        // NOTE: you also need to do allow access to the right users: $this->crud->allowAccess('details_row');
        // NOTE: you also need to do overwrite the showDetailsRow($id) method in your EntityCrudController to show whatever you'd like in the details row OR overwrite the views/backpack/crud/details_row.blade.php

        // ------ REVISIONS
        // You also need to use \Venturecraft\Revisionable\RevisionableTrait;
        // Please check out: https://laravel-backpack.readme.io/docs/crud#revisions
        // $this->crud->allowAccess('revisions');

        // ------ AJAX TABLE VIEW
        // Please note the drawbacks of this though:
        // - 1-n and n-n columns are not searchable
        // - date and datetime columns won't be sortable anymore
        // $this->crud->enableAjaxTable();

        // ------ DATATABLE EXPORT BUTTONS
        // Show export to PDF, CSV, XLS and Print buttons on the table view.
        // Does not work well with AJAX datatables.
        // $this->crud->enableExportButtons();

        // ------ ADVANCED QUERIES
        // $this->crud->addClause('active');
        // $this->crud->addClause('type', 'car');
        // $this->crud->addClause('where', 'name', '==', 'car');
        // $this->crud->addClause('whereName', 'car');
        // $this->crud->addClause('whereHas', 'posts', function($query) {
        //     $query->activePosts();
        // });
        // $this->crud->addClause('withoutGlobalScopes');
        // $this->crud->addClause('withoutGlobalScope', VisibleScope::class);
        // $this->crud->with(); // eager load relationships
        // $this->crud->orderBy();
        // $this->crud->groupBy();
        // $this->crud->limit();
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest as StoreRequest;
use App\Http\Requests\UserRequest as UpdateRequest;
use App\Topic;
use App\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;

class UserCrudController extends CrudController
{
    protected $user = null;

    public function setup()
    {
        $this->user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel(\App\User::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/user');
        $this->crud->setEntityNameStrings('user', 'users');

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */

        $this->crud->setFromDb();

        // ------ CRUD FIELDS
        $this->crud->addField([
            'label' => 'Roles',
            'type' => 'select2_multiple',
            'name' => 'roles',
            'entity' => 'roles',
            'attribute' => 'name',
            'model' => Role::class,
            'pivot' => true
        ], 'both');

        /*// ------ CRUD FIELDS
        $this->crud->addField([
            'label' => 'Topics',
            'type' => 'select2_multiple',
            'name' => 'topics',
            'entity' => 'topics',
            'attribute' => 'gene_symbol',
            'model' => Topic::class,
            'pivot' => false
        ], 'both');*/


        // ------ CRUD FIELDS
        $this->crud->addField([
            'label' => 'Permissions',
            'type' => 'select2_multiple',
            'name' => 'permissions',
            'entity' => 'permissions',
            'attribute' => 'name',
            'model' => Permission::class,
            'pivot' => true
        ], 'both');



        // ------ CRUD FIELDS
        // $this->crud->addField($options, 'update/create/both');
        // $this->crud->addFields($array_of_arrays, 'update/create/both');
        // $this->crud->removeField('name', 'update/create/both');
        // $this->crud->removeFields($array_of_names, 'update/create/both');

        // ------ CRUD COLUMNS
        // $this->crud->addColumn(); // add a single column, at the end of the stack
        // $this->crud->addColumns(); // add multiple columns, at the end of the stack
        // $this->crud->removeColumn('column_name'); // remove a column from the stack
        // $this->crud->removeColumns(['column_name_1', 'column_name_2']); // remove an array of columns from the stack
        // $this->crud->setColumnDetails('column_name', ['attribute' => 'value']); // adjusts the properties of the passed in column (by name)
        // $this->crud->setColumnsDetails(['column_1', 'column_2'], ['attribute' => 'value']);

        // ------ CRUD BUTTONS
        // possible positions: 'beginning' and 'end'; defaults to 'beginning' for the 'line' stack, 'end' for the others;
        // $this->crud->addButton($stack, $name, $type, $content, $position); // add a button; possible types are: view, model_function
        $this->crud->addButton('line', 'deactivate_user', 'model_function', 'deactivateUser', 'end');
        // $this->crud->addButtonFromModelFunction($stack, $name, $model_function_name, $position); // add a button whose HTML is returned by a method in the CRUD model
        // $this->crud->addButtonFromView($stack, $name, $view, $position); // add a button whose HTML is in a view placed at resources\views\vendor\backpack\crud\buttons
        // $this->crud->removeButton($name);
        // $this->crud->removeButtonFromStack($name, $stack);
        // $this->crud->removeAllButtons();
        // $this->crud->removeAllButtonsFromStack('line');


        // ------ CRUD ACCESS
        if( $this->user->hasPermissionTo('list users') ){
            $this->crud->allowAccess(['list']);
        }else{
            $this->crud->denyAccess(['list']);
        }
        if( $this->user->hasPermissionTo('create users') ){
            $this->crud->allowAccess(['create']);
        }else{
            $this->crud->denyAccess(['create']);
        }
        if( $this->user->hasPermissionTo('update users') ){
            $this->crud->allowAccess(['update']);
        }else{
            $this->crud->denyAccess(['update']);
        }
        if( $this->user->hasPermissionTo('deactivate users') ){
            $this->crud->allowAccess(['deactivate']);
        }else{
            $this->crud->denyAccess(['deactivate']);
        }
        if( $this->user->hasPermissionTo('delete users') ){
            $this->crud->allowAccess(['delete']);
        }else{
            $this->crud->denyAccess(['delete']);
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
        $this->crud->allowAccess('revisions');

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
        // do not update password if left blank
        if( $request['password'] === NULL ){
            unset($request['password']);
        }

        // your additional operations before save here
        $redirect_location = parent::updateCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }

    public function deactivate(Request $request)
    {
        if( $this->user->hasPermissionTo('deactivate users') ) {
            $user = User::findOrFail($request->id);
            $user->update(['deactivated_at'=>Carbon::now()]);
            return Redirect::back()->with(['msg','User '.$user->name.' deactivated successfully']);
        }else{
            return Redirect::back()->withErrors(['msg','Logged in user does not hae access to do deactivate users']);
        }
    }
}

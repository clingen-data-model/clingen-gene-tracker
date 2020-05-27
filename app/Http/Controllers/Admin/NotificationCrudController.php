<?php

namespace App\Http\Controllers\Admin;

use App\User;

// VALIDATION: change the requests to match your own file names if you need form validation
use Backpack\CRUD\CrudPanel;
use Illuminate\Http\Request;
use Backpack\CRUD\app\Http\Controllers\CrudController;

/**
 * Class NotificationCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class NotificationCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Notification');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/notification');
        $this->crud->setEntityNameStrings('notification', 'notifications');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        // TODO: remove setFromDb() and manually define Fields and Columns
        $this->crud->setFromDb();
        $this->crud->allowAccess('show');
        $this->crud->denyAccess('update');
        // $this->crud->denyAccess('delete');
        $this->crud->denyAccess('create');

        $this->crud->removeColumns(['notifiable_type']);
        
        $this->crud->addColumn([
            'name' => 'created_at',
            'type' => 'date',
            'label' => 'Created',
            'visibleInTable' => true,
            'priority' => 1
        ])->makeFirstColumn();

        $this->crud->setColumnDetails('notifiable_id', [
            'type' => 'text',
            'name' => 'notifiable.name',
            'priority' => 1,
            'searchLogic' => function ($query, $col, $term) {
                $query->orWhere(function ($q) use ($term) {
                    $q->whereHasMorph('notifiable', [User::class], function ($qu) use ($term) {
                        return $qu->where('name', 'LIKE', '%'.$term.'%');
                    });
                });
            }
        ]);

        $this->crud->modifyColumn('type', [
            'type' => 'text',
            'name' => 'readable_type',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('type', $searchTerm);
            },
            'priority' => 1
        ]);


        $this->crud->modifyColumn('read_at', [
            'visibleInTable' => true,
            'priority' => 1
        ]);

        $this->crud->modifyColumn('data', [
            'type' => 'json',
            'name' => 'data',
            'visibleInTable' => false,
            'visibleInShow' => true,
            'priority' => 1000000000
        ]);
    }

    public function store(Request $request)
    {
        // your additional operations before save here
        $redirect_location = parent::storeCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }

    public function update(Request $request)
    {
        // your additional operations before save here
        $redirect_location = parent::updateCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\EmailRequest as StoreRequest;
use App\Http\Requests\EmailRequest as UpdateRequest;
use Backpack\CRUD\CrudPanel;

/**
 * Class EmailCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class EmailCrudController extends CrudController
{    
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation {
        show as parentShow;
    }

    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Email');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/email');
        $this->crud->setEntityNameStrings('email', 'email');

        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */
        $this->crud->allowAccess('show');
        $this->crud->denyAccess('update');
        $this->crud->denyAccess('delete');
        $this->crud->denyAccess('create');
        // TODO: remove setFromDb() and manually define Fields and Columns
        $this->crud->setFromDb();

        $this->crud->removeColumns(['cc', 'bcc', 'reply_to', 'sender']);
        $this->crud->setColumnsDetails(['from','to'],
            ['type' => 'json_email']);
        $this->crud->setColumnsDetails(['body'], [
            'escaped' => false,
            'visibleInTable' => false,
        ]);
        $this->crud->addColumn([
            'type' => 'datetime',
            'name' => 'created_at',
            'label' => 'Sent',
            'format' => 'MM/DD/YYYY h:mm a',
        ])->makeFirstColumn();

        $this->crud->orderBy('created_at', 'DESC');
    }

    public function show($id)
    {
        $content = $this->parentShow($id);
        foreach ($content->entry->getAttributes() as $key => $value) {
            if (is_null($value)) {
                $this->crud->removeColumn($key);
            }
        }

        return $content;
    }
}

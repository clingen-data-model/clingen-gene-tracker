<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Affiliation;
use App\ExpertPanel;
use App\WorkingGroup;
use Illuminate\Support\Facades\Auth;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use App\Http\Requests\ExpertPanelRequest as StoreRequest;
use App\Http\Requests\ExpertPanelRequest as UpdateRequest;

class ExpertPanelCrudController extends CrudController
{    
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\ReviseOperation\ReviseOperation;

    protected $user = null;

    public function setUp(): void
    {
        $this->user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel(ExpertPanel::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/expert-panel');
        $this->crud->setEntityNameStrings('expert-panel', 'expert-panels');

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */

        $this->crud->setFromDb();
        $this->crud->removeFields(['working_group_id', 'affiliation_id']);

        $this->crud->addField([
            'name' => 'workingGroup',
            'type' => 'relationship'
        ]);

        $this->crud->addField([
            'name' => 'affiliation',
            'type' => 'relationship',
            'label'     => 'Affiliation',
            'attribute' => 'name',
        ]);

        // ------ COLUMNS

        $this->crud->addColumn([
            'name' => 'id',
            'label' => 'ID'
        ])->makeFirstColumn();

        $this->crud->setColumnDetails('name', [
            'limit' => 80,
        ]);

        $this->crud->setColumnDetails('working_group_id', [
           'label' => "Working Group", // Table column heading
           'type' => "select",
           'name' => 'working_group_id', // the column that contains the ID of that connected entity;
           'entity' => 'workingGroup', // the method that defines the relationship in your Model
           'attribute' => "name", // foreign key attribute that is shown to user
           'model' => WorkingGroup::class,
        ]);

        $this->crud->setColumnDetails('affiliation_id', [
            'label' => "Affiliation", // Table column heading
            'type' => "select",
            'name' => 'affiliation_id', // the column that contains the ID of that connected entity;
            'entity' => 'affiliation', // the method that defines the relationship in your Model
            'attribute' => "name", // foreign key attribute that is shown to user
            'model' => Affiliation::class,
            'limit' => 80,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $like = "%{$searchTerm}%";
                $query->orWhereHas('affiliation', function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                    ->orWhere('short_name', 'like', $like)
                    ->orWhereHas('type', function ($t) use ($like) {
                        $t->where('name', 'like', $like); // mirrors your accessor: name + type->name
                    });
                });
            },
         ]);

        // ------ CRUD ACCESS
        $this->crud->denyAccess(['list','create','update','delete']);
        if ($this->user->hasPermissionTo('list expert-panels')) {
            $this->crud->allowAccess(['list']);
        }
        if ($this->user->hasPermissionTo('create expert-panels')) {
            $this->crud->allowAccess(['create']);
        }
        if ($this->user->hasPermissionTo('update expert-panels')) {
            $this->crud->allowAccess(['update']);
        }
        if ($this->user->hasPermissionTo('delete expert-panels')) {
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

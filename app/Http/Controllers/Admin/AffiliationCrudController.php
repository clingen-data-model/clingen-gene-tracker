<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Affiliation;
use App\AffiliationType;
use Illuminate\Support\Facades\Auth;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use App\Http\Requests\Admin\AffiliationRequest as StoreRequest;
use App\Http\Requests\Admin\AffiliationRequest as UpdateRequest;

class AffiliationCrudController extends CrudController
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
        $this->crud->setModel(Affiliation::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/aff');
        $this->crud->setEntityNameStrings('affiliation', 'affiliations');

        /*
        |--------------------------------------------------------------------------
        | BASIC CRUD INFORMATION
        |--------------------------------------------------------------------------
        */

        $this->crud->setFromDb();

        // ------ FIELDS
        $this->crud->modifyField('affiliation_type_id', [
            'label' => 'Type',
            'type' => 'select2',
            'name' => 'affiliation_type_id',
            'entity' => 'type',
            'attribute' => 'name',
            'model' => AffiliationType::class
        ]);

        $this->crud->modifyField('parent_id', [
            'label' => 'Parent',
            'type' => 'select2',
            'name' => 'parent_id',
            'entity' => 'parent',
            'attribute' => 'name',
            'model' => Affiliation::class
        ]);

        $this->crud->modifyField('clingen_id', [
            'name' => 'clingen_id',
            'label' => 'Affiliation ID'
        ]);

        // // ------ COLUMNS
        $this->crud->addColumn('affiliation_id', [
            'name' => 'affiliation_id',
            'label' => 'Affiliation ID',
        ])->makeFirstColumn();
        $this->crud->modifyColumn('affiliation_type_id', [
            'label' => 'Type',
            'type' => 'select',
            'name' => 'affiliation_type_id',
            'entity' => 'type',
            'attribute' => 'name',
            'model' => AffiliationType::class
        ]);
        $this->crud->modifyColumn('parent_id', [
            'label' => 'Parent',
            'type' => 'select',
            'name' => 'parent_id',
            'entity' => 'parent',
            'attribute' => 'name',
            'model' => Affiliation::class
        ]);
        $this->crud->modifyColumn('clingen_id',[
            'name' => 'clingen_id',
            'label' => 'Affiliation ID'
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
        $this->crud->addField([
            'name' => 'id',
            'type' => 'hidden'
        ], 'update');
    }
}

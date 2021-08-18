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
        $this->crud->addField([
            'label' => 'Type',
            'type' => 'select2',
            'name' => 'affiliation_type_id',
            'entity' => 'type',
            'attribute' => 'name',
            'modle' => AffiliationType::class
        ]);

        $this->crud->addField([
            'label' => 'Parent',
            'type' => 'select2',
            'name' => 'parent_id',
            'entity' => 'parent',
            'attribute' => 'name',
            'modle' => Affiliation::class
        ]);

        $this->crud->addField([
            'name' => 'clingen_id',
            'label' => 'Affiliation ID'
        ]);

        $this->crud->addField([
            'name' => 'id',
            'type' => 'hidden'
        ], 'uupdate');

        // // ------ COLUMNS
        $this->crud->addColumns([
            'label' => 'Type',
            'type' => 'select',
            'name' => 'affiliation_type_id',
            'entity' => 'type',
            'attribute' => 'name',
            'modle' => AffiliationType::class
        ],[
            'label' => 'Parent',
            'type' => 'select',
            'name' => 'parent_id',
            'entity' => 'parent',
            'attribute' => 'name',
            'modle' => Affiliation::class
        ],[
            'name' => 'clingen_id',
            'label' => 'Affiliation ID'
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
        if ($this->user->hasPermissionTo('delete expert-panels')) {
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

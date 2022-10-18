<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApiClientCreate;
use App\ApiClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Prologue\Alerts\Facades\Alert;
use App\Actions\ApiClientCreateToken;
use App\Http\Requests\ApiClientRequest;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\ApiClientTokenRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ApiClientCrudControllerCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ApiClientCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as baseCreateStore;
    }

    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function __construct(
        private ApiClientCreateToken $createToken, 
        private ApiClientCreate $createClient
    )
    {
        parent::__construct();
    }
    

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(ApiClient::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/api-client');
        CRUD::setEntityNameStrings('api client', 'api clients');

        $this->crud->setShowView('vendor.backpack.crud.api_client_show');
        $this->crud->setEditView('vendor.backpack.crud.api_client_edit');
    }

    public function store () {
        $this->crud->hasAccessOrFail('create');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();

        // insert item in the db
        $data = $this->crud->getStrippedSaveRequest();
        $client = $this->createClient->handle(
            name: $data['name'],
            email: $data['contact_email']
        );
        $this->data['entry'] = $this->crud->entry = $client;

        // show a success message
        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($client->getKey());
    }

    public function createToken(Request $request)
    {
        if ($request->user()->hasAnyRole(['programmer', 'admin'])) {
            $client = ApiClient::find($request->id);
            $token = $this->createToken->handle($client, $client->name.'-'.Carbon::now()->toIsoString());

            $request->session()->flash('message', 'fuck laravel');

            $redirect = Redirect::back()
                ->with('newToken', [
                    'client' => $client,
                    'token' => $token->plainTextToken
                ]);

            return $redirect;
        }

        return Redirect::back()->withErrors(['msg' => 'You don\'t have permission to generate an api client token.']);
    }

    public function deleteToken(Request $request)
    {
        //code
    }
    

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb();
        CRUD::removeColumn('uuid');
        CRUD::addButtonFromView('line', 'create_client_token', 'create_client_token', 'end');

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ApiClientrequest::class);
        
        CRUD::setFromDb(); // fields
        Crud::removeField('uuid');

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
Crud::removeField('uuid');

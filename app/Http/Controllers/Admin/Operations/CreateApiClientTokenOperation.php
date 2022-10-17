<?php

namespace App\Http\Controllers\Admin\Operations;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

trait CreateApiClientTokenOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param string $segment    Name of the current entity (singular). Used as first URL segment.
     * @param string $routeName  Prefix of the route name.
     * @param string $controller Name of the current CrudController.
     */
    protected function setupCreateApiClientTokenRoutes($segment, $routeName, $controller)
    {
        Route::get($segment.'/create-token', [
            'as'        => $routeName.'.create-token',
            'uses'      => $controller.'@createToken',
            'operation' => 'createApiClientToken',
        ]);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     */
    protected function setupCreateApiClientTokenDefaults()
    {
        Auth::user()->hasAnyRole(['programmer', 'admin']);

        $this->crud->operation('createapiclienttoken', function () {
            $this->crud->loadDefaultOperationSettingsFromConfig();
        });

        $this->crud->operation('list', function () {
            // $this->crud->addButton('top', 'createapiclienttoken', 'view', 'crud::buttons.createapiclienttoken');
            // $this->crud->addButton('line', 'createapiclienttoken', 'view', 'crud::buttons.createapiclienttoken');
        });
    }

    /**
     * Show the view for performing the operation.
     *
     * @return Response
     */
    public function createapiclienttoken()
    {
        $this->crud->hasAccessOrFail('createapiclienttoken');

        // prepare the fields you need to show
        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? 'createapiclienttoken '.$this->crud->entity_name;

        // load the view
        return view("crud::operations.createapiclienttoken", $this->data);
    }
}

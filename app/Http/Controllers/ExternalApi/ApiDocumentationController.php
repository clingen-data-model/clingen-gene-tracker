<?php

namespace App\Http\Controllers\ExternalApi;

use App\Http\Controllers\Controller;

class ApiDocumentationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $openapi = \OpenApi\Generator::scan([app_path()]);

        return response($openapi->toYaml(), 200, ['Content-type' => 'application/x-yaml']);
    }
}

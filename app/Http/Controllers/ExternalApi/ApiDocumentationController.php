<?php

namespace App\Http\Controllers\ExternalApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class ApiDocumentationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $openapi = \OpenApi\Generator::scan([app_path()]);

        return response($openapi->toYaml(), 200, ['Content-type' => 'application/x-yaml']);
    }
}

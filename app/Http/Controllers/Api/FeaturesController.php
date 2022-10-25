<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class FeaturesController extends Controller
{
    public function index()
    {
        return [
            'transferEnabled' => config('app.transfers_enabled'),
            'sendToGciEnabled' => config('app.send-to-gci-enabled'),
        ];
    }
    
}

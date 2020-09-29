<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TimeoutTestController extends Controller
{
    public function index(Request $request)
    {
        $output = $request->all();
        if ($request->has('use_db')) {
            $users = \DB::table('users')->take(100)->get();
            $output['users'] = $users;
        }

        if ($request->has('use_cache')) {
            Cache::put('timeout-test', Carbon::now()->format('Y-m-d H:i:s'));
            $output['cache'] = Cache::get('timeout-test');
        }

        if ($request->has('use_view')) {
            return view('timeout_test', ['data' => $output]);
        }

        return $output;
    }
}

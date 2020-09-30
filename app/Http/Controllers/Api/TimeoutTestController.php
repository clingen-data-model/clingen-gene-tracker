<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Profiling\TaskTimeSingleton;

class TimeoutTestController extends Controller
{
    public function index(Request $request)
    {
        $timer = TaskTimeSingleton::init();

        $timer->addEvent(__METHOD__.': entered TimeoutTestController');

        $output = $request->all();
        if ($request->has('use_db')) {
            $users = \DB::table('users')->take(100)->get();
            $output['users'] = $users;
            $timer->addEvent(__METHOD__.': Queried database for users.');
        }

        if ($request->has('use_cache')) {
            Cache::put('timeout-test', Carbon::now()->format('Y-m-d H:i:s'));
            $output['cache'] = Cache::get('timeout-test');
            $timer->addEvent(__METHOD__.': Did some stuff with the cache.');
        }

        if ($request->has('use_view')) {
            $view = view('timeout_test', ['data' => $output]);
            $timer->addEvent(__METHOD__.': Renderd a view.');

            return $view;
        }

        return $output;
    }
}

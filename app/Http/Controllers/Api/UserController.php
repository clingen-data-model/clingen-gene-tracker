<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserResource;
use App\Http\Resources\UserResource;
use App\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        if ($request->has('role')) {
            $query->role(explode(',', $request->role));
        }
        
        if ($request->has('with')) {
            $query->with(explode(',', $request->with));
        }
        return UserResource::collection($query->get());
    }

    public function currentUser()
    {
        $user = \Auth::guard('api')->user();
        $user->load('roles', 'permissions', 'preferences');
        $user->permissions = $user->getAllPermissions();

        return new CurrentUserResource($user);
    }
}

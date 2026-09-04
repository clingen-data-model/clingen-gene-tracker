<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserResource;
use App\Http\Resources\UserResource;
use App\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('list users'), 403);

        return User::query()
            ->with(['roles:id,name', 'permissions:id,name'])
            ->withCount(['expertPanels', 'affiliations'])
            ->orderBy('name')
            ->get();
    }

    public function adminOptions(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('list users'), 403);

        return [
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function adminUpdate(UserRequest $request, User $user)
    {
        DB::transaction(function () use ($request, $user) {
            $user->update($request->only(['name', 'email']));
            $user->syncRoles(Role::query()->whereIn('id', $request->validated('role_ids'))->get());
            $user->syncPermissions(Permission::query()->whereIn('id', $request->validated('permission_ids'))->get());
        });

        return $this->loadAdminRelationships($user->fresh());
    }

    public function deactivate(Request $request, User $user)
    {
        abort_unless($request->user()->hasPermissionTo('deactivate users'), 403);
        $user->update(['deactivated_at' => now()]);

        return $this->loadAdminRelationships($user->fresh());
    }

    public function reactivate(Request $request, User $user)
    {
        abort_unless($request->user()->hasPermissionTo('deactivate users'), 403);
        $user->update(['deactivated_at' => null]);

        return $this->loadAdminRelationships($user->fresh());
    }

    private function loadAdminRelationships(User $user): User
    {
        return $user
            ->load(['roles:id,name', 'permissions:id,name'])
            ->loadCount(['expertPanels', 'affiliations']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserResource;
use App\Http\Resources\UserResource;
use App\User;
use App\ExpertPanel;
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

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $search = trim($request->validate(['search' => ['nullable', 'string', 'max:200']])['search'] ?? '');

        return User::query()
            ->with(['roles:id,name', 'permissions:id,name', 'expertPanels:id,name'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->withCount(['expertPanels', 'affiliations'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function adminOptions(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('list users'), 403);

        return [
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']),
            'expert_panels' => ExpertPanel::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function adminStore(UserRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            // Assign explicitly to bypass the model's legacy default credential.
            // The normal User password mutator hashes this 256-bit random value.
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => bin2hex(random_bytes(32)),
            ]);
            $this->syncAdminAssociations($request, $user);

            return $user;
        });

        return response()->json($this->loadAdminRelationships($user), 201);
    }

    public function adminUpdate(UserRequest $request, User $user)
    {
        DB::transaction(function () use ($request, $user) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $user->update($request->only(['name', 'email']));
            $this->syncAdminAssociations($request, $user);
        });

        return $this->loadAdminRelationships($user->fresh());
    }

    private function syncAdminAssociations(UserRequest $request, User $user): void
    {
        $user->syncRoles(Role::query()->whereIn('id', $request->validated('role_ids'))->get());
        $user->syncPermissions(Permission::query()->whereIn('id', $request->validated('permission_ids'))->get());
        if ($request->has('expert_panels')) {
            $memberships = collect($request->validated('expert_panels'))->mapWithKeys(function ($panel) {
                return [$panel['id'] => [
                    'is_curator' => $panel['is_curator'],
                    'is_coordinator' => $panel['is_coordinator'],
                    'can_edit_curations' => $panel['can_edit_curations'],
                ]];
            })->all();
            $user->expertPanels()->sync($memberships);
        }
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
            ->load(['roles:id,name', 'permissions:id,name', 'expertPanels:id,name'])
            ->loadCount(['expertPanels', 'affiliations']);
    }
}

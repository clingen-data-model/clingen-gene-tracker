<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\ExpertPanelResolver;

class PanelMembersApiController extends Controller
{
    use ApiResponse;

    public function sync(Request $request)
    {
        $v = Validator::make($request->all(), [
            'affiliation_id' => 'required|integer',
            'members'        => 'required|array|min:1',
            'members.*.email'          => 'required|email',
            'members.*.name'           => 'nullable|string',
            'members.*.is_coordinator' => 'required|boolean',
            'members.*.is_curator'     => 'required|boolean',
            'mode' => 'nullable|in:add,replace',
        ]);

        if ($v->fails()) {
            return $this->errorResponse('Validation failed', 422, $v->errors());
        }

        $clingenId = (int) $request->input('affiliation_id');
        if (! $clingenId) { return $this->errorResponse('Affiliation ID is required for Gene Bulk Upload', 422); }
        
        try {
            $resolved = app(ExpertPanelResolver::class)->ensureForClinGen($clingenId, 'Members Sync');
        } catch (\RuntimeException $e) {
            return $this->errorResponse('Affiliation not found', 404, ['affiliation ID' => $clingenId]);
        }
        $epID = $resolved['expert_panel_id'];
        $aff  = $resolved['affiliation'];

        $members   = collect($request->input('members', []))
            ->map(function ($m) {
                $m['email'] = strtolower(trim($m['email']));
                $m['name']  = isset($m['name']) ? trim($m['name']) : null;
                $m['is_coordinator'] = (int) !!$m['is_coordinator'];
                $m['is_curator']     = (int) !!$m['is_curator'];
                return $m;
            })
            ->unique('email')
            ->values();

        $mode = $request->input('mode', 'add');

        // 3) Upsert users + pivot
        $createdUsers = [];
        $updatedUsers = [];
        $linkedEmails = [];

        DB::transaction(function () use ($members, $epID, &$createdUsers, &$updatedUsers, &$linkedEmails, $mode) {
            $emailToId = DB::table('users')
                ->whereIn('email', $members->pluck('email'))
                ->pluck('id', 'email')
                ->mapWithKeys(fn ($id, $email) => [strtolower($email) => $id]);

            foreach ($members as $m) {
                $email = $m['email'];
                $name  = $m['name'] ?: $email;

                $userId = $emailToId[$email] ?? null;

                if (!$userId) { // If member doesn't exist, create them
                    $userId = DB::table('users')->insertGetId([
                        'email'      => $email,
                        'name'       => $name . ' (Auto-created From Members Sync)',
                        'password'   => bcrypt(str()->random(32)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $createdUsers[] = $email;
                    $emailToId[$email] = $userId;
                } else {
                    // Sync name if provided
                    if ($name && $name !== DB::table('users')->where('id', $userId)->value('name')) {
                        DB::table('users')->where('id', $userId)->update(['name' => $name, 'updated_at' => now()]);
                        $updatedUsers[] = $email;
                    }
                }

                DB::table('expert_panel_user')->updateOrInsert(
                    ['expert_panel_id' => $epID, 'user_id' => $userId],
                    [
                        'is_coordinator'     => $m['is_coordinator'],
                        'is_curator'         => $m['is_curator'],
                        'can_edit_curations' => $m['is_coordinator'],
                        'updated_at'         => now(),
                        'created_at'         => now(),
                    ]
                );

                $linkedEmails[] = $email;
            }

            if ($mode === 'replace') {
                $keepIds = array_values(array_unique(array_map(fn ($e) => $emailToId[$e], $linkedEmails)));
                DB::table('expert_panel_user')
                    ->where('expert_panel_id', $epID)
                    ->whereNotIn('user_id', $keepIds)
                    ->delete();
            }
        });

        return $this->successResponse([
            'expert_panel_id' => $epID,
            'created_users'   => $createdUsers,
            'updated_users'   => $updatedUsers,
            'linked_emails'   => $linkedEmails,
        ], 'Members synced');
    }
}

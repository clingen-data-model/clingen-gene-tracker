<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Affiliation;
use App\Email;
use App\ExpertPanel;
use App\User;
use App\WorkingGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPassThreeTest extends TestCase
{
    private User $admin;

    public function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $role = Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']);
        foreach (['list users', 'update users', 'list expert-panels', 'create expert-panels', 'update expert-panels', 'list working-groups'] as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        $this->admin = factory(User::class)->create();
        $this->admin->assignRole($role);
        $this->actingAs($this->admin, 'api');
    }

    private function payload(User $user, array $panels): array
    {
        return ['name' => $user->name, 'email' => $user->email, 'role_ids' => [], 'permission_ids' => [], 'expert_panels' => $panels];
    }

    private function membership(int $id, bool $curator = false, bool $coordinator = false, bool $editor = false): array
    {
        return ['id' => $id, 'is_curator' => $curator, 'is_coordinator' => $coordinator, 'can_edit_curations' => $editor];
    }

    public function test_memberships_are_loaded_added_updated_and_removed_with_all_flags(): void
    {
        $user = factory(User::class)->create(['name' => 'PassThree Membership User']);
        $panels = factory(ExpertPanel::class, 3)->create();
        $user->expertPanels()->attach($panels[0]->id, ['is_curator' => true]);
        $this->getJson('/api/admin/users?search=PassThree%20Membership')->assertOk()
            ->assertJsonPath('data.0.expert_panels.0.id', $panels[0]->id)
            ->assertJsonPath('data.0.expert_panels.0.pivot.is_curator', 1);

        $payload = $this->payload($user, [
            $this->membership($panels[0]->id, false, true, true),
            $this->membership($panels[1]->id, true),
            $this->membership($panels[2]->id),
        ]);
        $this->putJson('/api/admin/users/'.$user->id, $payload)->assertOk()->assertJsonPath('expert_panels_count', 3);
        $this->putJson('/api/admin/users/'.$user->id, $payload)->assertOk();
        $this->assertSame(3, $user->expertPanels()->count());
        $this->assertDatabaseHas('expert_panel_user', ['user_id' => $user->id, 'expert_panel_id' => $panels[0]->id, 'is_curator' => 0, 'is_coordinator' => 1, 'can_edit_curations' => 1]);
        $this->assertDatabaseHas('expert_panel_user', ['user_id' => $user->id, 'expert_panel_id' => $panels[2]->id, 'is_curator' => 0, 'is_coordinator' => 0, 'can_edit_curations' => 0]);
        $this->putJson('/api/admin/users/'.$user->id, $this->payload($user, []))->assertOk()->assertJsonPath('expert_panels_count', 0);
        $this->assertSame(0, $user->expertPanels()->count());
    }

    public function test_invalid_or_duplicate_memberships_do_not_partially_update_the_user(): void
    {
        $user = factory(User::class)->create();
        $panel = factory(ExpertPanel::class)->create();
        $user->expertPanels()->attach($panel->id, ['is_curator' => true]);
        foreach ([
            [$this->membership($panel->id), $this->membership($panel->id)],
            [$this->membership(99999999)],
            [array_merge($this->membership($panel->id), ['can_edit_curations' => 'bad'])],
        ] as $memberships) {
            $payload = array_merge($this->payload($user, $memberships), ['name' => 'Should Not Save']);
            $this->putJson('/api/admin/users/'.$user->id, $payload)->assertUnprocessable();
            $this->assertSame($user->name, $user->fresh()->name);
            $this->assertSame(1, $user->expertPanels()->count());
            $this->assertDatabaseHas('expert_panel_user', ['user_id' => $user->id, 'expert_panel_id' => $panel->id, 'is_curator' => 1]);
        }
    }

    public function test_affiliation_selection_rejects_missing_and_deleted_records(): void
    {
        $affiliation = factory(Affiliation::class)->create(['clingen_id' => 891001]);
        $affiliation->delete();
        foreach ([99999999, $affiliation->id] as $id) {
            $this->postJson('/api/admin/expert-panels', ['name' => 'PassThree Invalid Panel', 'affiliation_id' => $id])
                ->assertUnprocessable()->assertJsonValidationErrors('affiliation_id');
        }
        $this->getJson('/api/admin/expert-panels/options')->assertOk()->assertJsonMissing(['id' => $affiliation->id, 'clingen_id' => 891001]);
    }

    private function assertSearch(string $endpoint, string $term, array $ids, bool $paginated = true): void
    {
        $query = http_build_query(['search' => $term, 'per_page' => 1]);
        if (!$paginated) {
            $response = $this->getJson('/api/admin/'.$endpoint.'?'.$query)->assertOk();
            $this->assertEqualsCanonicalizing($ids, array_column($response->json(), 'id'));
            return;
        }
        $found = [];
        for ($page = 1; $page <= max(count($ids), 1); $page++) {
            $response = $this->getJson('/api/admin/'.$endpoint.'?'.$query.'&page='.$page)->assertOk()->assertJsonPath('total', count($ids));
            $found = array_merge($found, array_column($response->json('data'), 'id'));
        }
        $this->assertEqualsCanonicalizing($ids, $found);
    }

    public function test_user_search_covers_name_and_email_before_pagination(): void
    {
        $a = factory(User::class)->create(['name' => 'PassThree Needle One']);
        $b = factory(User::class)->create(['email' => 'passthree.needle@example.com']);
        $this->assertSearch('users', 'PassThree', [$a->id, $b->id]);
        $this->assertSearch('users', 'passthree.needle@', [$b->id]);
    }

    public function test_expert_panel_search_covers_panel_and_affiliation_identifiers(): void
    {
        $affiliation = factory(Affiliation::class)->create(['name' => 'PassThree Affiliation', 'short_name' => 'PT Needle', 'clingen_id' => 891002]);
        $a = factory(ExpertPanel::class)->create(['name' => 'PassThree Panel']);
        $b = factory(ExpertPanel::class)->create(['affiliation_id' => $affiliation->id]);
        $this->assertSearch('expert-panels', 'PassThree', [$a->id, $b->id]);
        $this->assertSearch('expert-panels', '891002', [$b->id]);
        $this->assertSearch('expert-panels', 'PT Needle', [$b->id]);
    }

    public function test_affiliation_search_covers_name_short_name_and_clingen_id(): void
    {
        $a = factory(Affiliation::class)->create(['name' => 'PassThree Affiliation', 'clingen_id' => 891003]);
        $b = factory(Affiliation::class)->create(['short_name' => 'PassThree', 'clingen_id' => 891004]);
        $this->assertSearch('affiliations', 'PassThree', [$a->id, $b->id]);
        $this->assertSearch('affiliations', '891004', [$b->id]);
    }

    public function test_working_group_search_covers_name(): void
    {
        $group = factory(WorkingGroup::class)->create(['name' => 'PassThree Working Group']);
        $this->assertSearch('working-groups', 'PassThree', [$group->id], false);
    }

    public function test_email_search_covers_subject_and_addresses_but_not_html_body(): void
    {
        $a = Email::create(['subject' => 'PassThree Subject', 'from' => ['passthree-source@example.com' => 'Sender'], 'to' => ['recipient@example.com' => 'Recipient']]);
        $b = Email::create(['subject' => 'Other', 'from' => ['another@example.com' => 'Sender'], 'to' => ['passthree@example.com' => 'Recipient'], 'sender' => ['passthree-source@example.com' => 'Sender']]);
        Email::create(['subject' => 'Body only', 'from' => ['body@example.com' => 'Sender'], 'to' => ['recipient@example.com' => 'Recipient'], 'body' => '<p>PassThree</p>']);
        $this->assertSearch('emails', 'PassThree', [$a->id, $b->id]);
        $this->assertSearch('emails', 'passthree-source@example.com', [$a->id, $b->id]);
    }

    public function test_notification_search_covers_type_and_recipient_without_scanning_payload(): void
    {
        $recipient = factory(User::class)->create(['name' => 'PassThree Recipient', 'email' => 'passthree-recipient@example.com']);
        $ids = [(string) Str::uuid(), (string) Str::uuid()];
        foreach ($ids as $id) {
            DB::table('notifications')->insert(['id' => $id, 'type' => 'App\\Notifications\\PassThreeNotice', 'notifiable_type' => User::class, 'notifiable_id' => $recipient->id, 'data' => '{"message":"PayloadOnlyNeedle"}', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->assertSearch('notifications', 'PassThreeNotice', $ids);
        $this->assertSearch('notifications', 'PassThree Recipient', $ids);
        $this->assertSearch('notifications', 'passthree-recipient@', $ids);
        $this->assertSearch('notifications', 'PayloadOnlyNeedle', []);
    }

    public function test_search_and_options_keep_authorization_boundaries(): void
    {
        $viewer = factory(User::class)->create();
        $this->actingAs($viewer, 'api');
        foreach (['users', 'expert-panels', 'affiliations', 'working-groups', 'emails', 'notifications', 'users/options', 'expert-panels/options'] as $endpoint) {
            $this->getJson('/api/admin/'.$endpoint.'?search=PassThree')->assertForbidden();
        }
        $this->putJson('/api/admin/users/'.$this->admin->id, $this->payload($this->admin, []))->assertForbidden();
    }
}

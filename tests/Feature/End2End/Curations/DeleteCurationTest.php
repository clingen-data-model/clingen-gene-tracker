<?php

namespace Tests\Feature\End2End\Curations;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteCurationTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(\App\User::class)->create();
        $this->curation = factory(\App\Curation::class)->create(['curator_id' => $this->user->id]);
    }


    /**
     * @test
     * @group authorization
     */
    public function must_have_delete_permissions_to_delete_curation()
    {
        $curation = $this->curation;
        $curation->expertPanel->users()->attach($this->user->id, ['is_coordinator' => 0, 'can_edit_curations' => 1, 'is_curator' => 1]);

        $this->actingAs($this->user, 'api')
            ->json('DELETE', '/api/curations/'.$curation->id)
            ->assertStatus(403);
    }

    /**
     * @test
     * @group authorization
     */
    public function user_can_delete_a_curation_if_they_are_the_curator_of_curation_and_has_delete_permission()
    {
        $this->user->givePermissionTo('delete curations');
        $curation = $this->curation;

        $this->actingAs($this->user, 'api')
            ->json('DELETE', '/api/curations/'.$curation->id)
            ->assertStatus(200);
    }

    /**
     * @test
     * @group authorization
     */
    public function user_can_delete_a_curation_if_they_are_a_coordinator_of_expert_panel_that_owns_the_curation()
    {
        //create a coordinator who's not the curator
        $coordinator = factory(User::class)->create();

        // Get a curation and make the coordinator a coordinator on the
        // associated expert panel
        $curation = $this->curation;
        $curation->expertPanel->addCoordinator($coordinator);

        $this->actingAs($coordinator, 'api')
            ->json('DELETE', '/api/curations/'.$curation->id)
            ->assertStatus(200);
    }

    /**
     * @test
     * @group authorization
     */
    public function user_with_panel_curation_edit_perms_and_delete_curation_permission_can_delete_a_curation()
    {
        //create a user who's not the curator
        $user = factory(User::class)->create();
        $user->givePermissionTo('delete curations');

        // Get a curation and make the user a user on the
        // associated expert panel
        $curation = $this->curation;
        $curation->expertPanel->users()->attach($user->id, ['can_edit_curations' => 1]);

        $this->actingAs($user, 'api')
            ->json('DELETE', '/api/curations/'.$curation->id)
            ->assertStatus(200);
    }

}

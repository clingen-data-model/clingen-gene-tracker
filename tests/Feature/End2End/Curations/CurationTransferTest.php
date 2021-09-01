<?php

namespace Tests\Feature\End2End\Curations;

use App\User;
use App\Curation;
use Carbon\Carbon;
use Tests\TestCase;
use App\ExpertPanel;
use Illuminate\Support\Facades\Mail;
use App\Mail\Curations\TransferNotification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group curation-transfer
 */
class CurationTransferTest extends TestCase
{
    use DatabaseTransactions;

    protected $fakeCurationSavedEvent = false;

    public function setup():void
    {
        parent::setup();

        Carbon::setTestNow('2020-01-01');
        $this->ep1User = factory(User::class)->create();
        $this->ep2User = factory(User::class)->create();
        $eps = factory(ExpertPanel::class, 2)->create();
        $this->ep2 = $eps->pop();
        $this->ep2->addCoordinator($this->ep2User);
        
        $this->ep1 = $eps->pop();
        $this->ep1->addCoordinator($this->ep1User);

        $this->curation = factory(Curation::class)->create([
            'expert_panel_id' => $this->ep1->id
        ]);
        $this->url = '/api/curations/'.$this->curation->id.'/owner';
        Carbon::setTestNow('2021-04-01');
    }

    /**
     * @test
     */
    public function an_unpriveleged_user_cannot_transfer_a_curation()
    {
        // $this->withoutExceptionHandling();
        $this->actingAs($this->ep2User, 'api')
            ->json(
                'POST', 
                $this->url, 
                [
                    'expert_panel_id' => $this->ep2->id,
                    'start_date' => Carbon::now()->format('Y-m-d'),
                    'end_date' => null
                ]
            )
            ->assertStatus(403)
            ->assertJson(['error' => 'You do not have permission to transfer ownership of this curation']);
    }
    
    /**
     * @test
     */
    public function a_privileged_user_can_transfer_a_curation()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->ep1User, 'api')
            ->json('POST', $this->url, ['expert_panel_id' => $this->ep2->id, 'start_date'=>Carbon::now()->format('Y-m-d')])
            ->assertStatus(200)
            ->assertJson([
                'curation_id' => $this->curation->id,
                'expert_panels' => [
                    [
                        'id' => $this->ep2->id,
                        'name' => $this->ep2->name,
                        'affiliation_id' => $this->ep2->affiliation_id,
                        'working_group_id' => $this->ep2->working_group_id,
                        'start_date' => '2021-04-01T04:00:00.000000Z',
                        'end_date' => null,
                    ],
                    [
                        'id' => $this->ep1->id,
                        'name' => $this->ep1->name,
                        'affiliation_id' => $this->ep1->affiliation_id,
                        'working_group_id' => $this->ep1->working_group_id,
                        'start_date' => '2020-01-01T05:00:00.000000Z',
                        'end_date' => '2021-04-01T04:00:00.000000Z',
                    ]
                ]
            ]);

        $this->assertDatabaseHas('curations', [
            'id' => $this->curation->id,
            'expert_panel_id' => $this->ep2->id
        ]);
    }
    
    /**
     * @test
     */
    public function receiving_ep_coordinators_are_emailed_on_transfer()
    {
        $this->withoutExceptionHandling();

        $ep3User = factory(User::class)->create();
        $this->ep2->addCoordinator($ep3User);

        Mail::fake();
        
        $this->actingAs($this->ep1User, 'api')
            ->json('POST', $this->url, ['expert_panel_id' => $this->ep2->id, 'start_date'=>Carbon::now()->format('Y-m-d')])
            ->assertStatus(200);

        Mail::assertSent(
            TransferNotification::class,
            function ($mail) use ($ep3User) {
                return $mail->curation->id == $this->curation->id
                    && $mail->previousEp->id == $this->ep1->id
                    && $mail->hasTo($this->ep2User->email)
                    && $mail->hasTo($ep3User->email);
            }
        );
    }
    
    /**
     * @test
     */
    public function adds_a_note_if_notes_field_not_null()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->ep1User, 'api')
            ->json(
                'POST', 
                $this->url, 
                [
                    'expert_panel_id' => $this->ep2->id, 
                    'start_date'=>Carbon::now()->format('Y-m-d'),
                    'notes' => 'They fuck you up your mum and dad; They do not mean to, but they do.'
                ]
            )
            ->assertStatus(200)
            ;
                
        $this->assertDatabaseHas('notes', [
            'subject_type' => get_class($this->curation),
            'subject_id' => $this->curation->id,
            'content' => 'They fuck you up your mum and dad; They do not mean to, but they do.',
            'topic' => 'curation transfer'
        ]);
    
    }

    /**
     * @test
     */
    public function does_not_create_a_test_if_notes_field_is_null()
    {
        $this->actingAs($this->ep1User, 'api')
            ->json('POST', $this->url, ['expert_panel_id' => $this->ep2->id, 'start_date'=>Carbon::now()->format('Y-m-d')])
            ->assertStatus(200);
        
        $this->assertDatabaseMissing('notes', [
            'subject_type' => get_class($this->curation),
            'subject_id' => $this->curation->id
        ]);


    }
    

    
}

<?php

namespace Tests\Feature;

use App\User;
use App\Curation;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group gci
 */
class GciSnapshotImportTest extends TestCase
{
    use DatabaseTransactions;

    public function setup(): void
    {
        parent::setup();

        $this->user = factory(User::class)->create([
            'email' => 'agrant@broadinstitute.org',
            'name' => 'Andrew Grant',
        ]);

        $this->curation1 = factory(Curation::class)->create([
            'gene_symbol' => 'A2ML1',
            'hgnc_id' => '23336',
            'mondo_id' => 'MONDO:0007893',
        ]);
        $this->testFilePath = base_path('tests/files/test_gci_snapshot.csv');
    }

    /**
     * @test
     */
    public function outputs_notice_if_hgnc_mondo_dont_match_curation()
    {
        $this->artisan('gci:snapshot '.$this->testFilePath)
            ->expectsOutput('Curation with HGNC_ID HGNC:12345 and MonDO ID 12345 could not be found');
    }

    /**
     * @test
     */
    public function updates_curation_status_for_hgnc_mondo_id_match()
    {
        \Artisan::call('gci:snapshot '.$this->testFilePath);
        $curation = $this->curation1->fresh();

        $this->assertEquals('Published', $curation->currentStatus->name);
        // $this->assertEquals(Carbon::now()->startOfDay(), $curation->currentStatus->pivot->status_date->startOfDay());
    }

    /**
     * @test
     */
    public function updates_curation_classifciation_for_hgnc_mondo_id_match()
    {
        \Artisan::call('gci:snapshot '.$this->testFilePath);
        $curation = $this->curation1->fresh();

        $this->assertEquals('No Known Disease Relationship', $curation->currentClassification->name);
        // $this->assertEquals(Carbon::now()->startOfDay(), $curation->currentClassification->pivot->status_date->startOfDay());
    }

    /**
     * @test
     */
    public function adds_gdm_uuid_to_curation()
    {
        \Artisan::call('gci:snapshot '.$this->testFilePath);
        $curation = $this->curation1->fresh();

        $this->assertEquals('c3fd159d-9678-47a2-9cff-d1c9d0411d43', $curation->gdm_uuid);
    }

    /**
     * @test
     */
    public function adds_gci_uuid_to_existing_curator()
    {
        \Artisan::call('gci:snapshot '.$this->testFilePath);

        $this->assertEquals('4acafdd5-80f3-47f0-8522-f4bd04da175f', $this->user->fresh()->gci_uuid);
    }

    /**
     * @test
     */
    public function adds_user_as_curator_if_no_curator()
    {
        \Artisan::call('gci:snapshot '.$this->testFilePath);

        $this->assertDatabaseHas('curations', [
            'id' => $this->curation1->id,
            'curator_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     */
    public function creates_curator_if_user_with_email_does_not_exist()
    {
        $this->user->forceDelete();

        \Artisan::call('gci:snapshot '.$this->testFilePath);

        $this->assertDatabaseHas('users', [
            'email' => 'agrant@broadinstitute.org',
            'gci_uuid' => '4acafdd5-80f3-47f0-8522-f4bd04da175f',
        ]);

        $user = User::where('email', 'agrant@broadinstitute.org')->first();

        $this->assertDatabaseHas('curations', [
            'id' => $this->curation1->id,
            'curator_id' => $user->id,
        ]);

        $this->assertNotNull($user['deactivated_at']);
    }

    /**
     * @test
     */
    public function updates_MOI()
    {
        $this->artisan('gci:snapshot '.$this->testFilePath);

        $curation = $this->curation1->fresh();

        $this->assertEquals(1, $curation->moi_id);
    }
}

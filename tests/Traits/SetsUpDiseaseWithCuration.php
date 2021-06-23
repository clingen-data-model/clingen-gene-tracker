<?php

namespace Tests\Traits;

use App\User;
use App\Disease;
use App\Curation;

/**
 * 
 */
trait SetsUpDiseaseWithCuration
{
    public function setupDiseaseWithCuration(Array $diseaseData = [], $curationData = [])
    {
        $this->disease = factory(Disease::class)->create($diseaseData);
        $curationData = array_merge($curationData, ['mondo_id' => $this->disease->mondo_id]);
        $this->curation = factory(Curation::class)->create(['mondo_id'=>$this->disease->mondo_id]);
        $this->user1 = factory(User::class)->create();
        $this->curation->expertPanel->addCoordinator($this->user1);
    }
    
}

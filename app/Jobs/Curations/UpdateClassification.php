<?php

namespace App\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateClassification
{
    use Dispatchable, Queueable;

    protected $curation;

    protected $curationClassificationId;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, int $curationClassificationId, array $data)
    {
        //
        $this->curation = $curation;
        $this->curationClassificationId = $curationClassificationId;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $relatedClassification = $this->curation
                                    ->classifications
                                    ->firstWhere('pivot.id', $this->curationClassificationId);
        
        $relatedClassification->pivot->update([
            'classification_id' => $this->data['classification_id'],
            'classification_date' => $this->data["classification_date"]
        ]);
    }
}

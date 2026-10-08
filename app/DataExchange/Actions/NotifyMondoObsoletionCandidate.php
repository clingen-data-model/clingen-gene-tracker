<?php
namespace App\DataExchange\Actions;

use App\Curation;
use Illuminate\Support\Facades\Bus;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\DataExchange\Notifications\MondoObsoletionCandidateNotification;

class NotifyMondoObsoletionCandidate
{
    public function handle($data): void
    {
        Curation::mondoId($data->content->mondo_id)->get()
            ->each(function ($curation) use ($data) {
                Bus::dispatch(
                    (new NotifyCoordinatorsAboutCuration(
                        $curation, 
                        MondoObsoletionCandidateNotification::class, 
                        $data
                    ))->withDigestEventId('mondo-candidate:'.\App\Notifications\DigestEventIdentity::hash([
                        'curation_id' => $curation->id,
                        'message' => $data,
                    ]))
                );
            });
    }
    
}

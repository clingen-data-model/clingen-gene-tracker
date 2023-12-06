<?php

namespace App\DataExchange\Actions;

use App\Curation;
use App\DataExchange\Notifications\MondoObsoletionCandidateNotification;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use Illuminate\Support\Facades\Bus;

class NotifyMondoObsoletionCandidate
{
    public function handle($data): void
    {
        Curation::mondoId($data->content->mondo_id)->get()
            ->each(function ($curation) use ($data) {
                Bus::dispatch(
                    new NotifyCoordinatorsAboutCuration(
                        $curation,
                        MondoObsoletionCandidateNotification::class,
                        $data
                    )
                );
            });
    }
}

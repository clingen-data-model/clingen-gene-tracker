<?php

namespace App\Listeners\Curations;

use App\ExpertPanel;
use App\Events\Curation\CurrentOwnerChanged;
use App\Mail\Curations\TransferNotification;
use Illuminate\Support\Facades\Mail;

class SendTransferNotification
{
    public function handle(CurrentOwnerChanged $event): void
    {
        if (config('curations.replaying')) {
            return;
        }

        $curation = $event->curation->fresh();
        $newOwner = $curation->expertPanel;

        if (!$newOwner || !$newOwner->hasCoordinators()) {
            return;
        }

        // TransferNotification requires a previous panel and CCs its coordinators
        // itself. A curation reaching its first owner is not a transfer.
        $previousOwner = $event->previousExpertPanelId
            ? ExpertPanel::find($event->previousExpertPanelId)
            : null;

        if (!$previousOwner) {
            return;
        }

        Mail::to($newOwner->coordinators)
            ->send(new TransferNotification($curation, $previousOwner));
    }
}

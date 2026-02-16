<?php

namespace App\DataExchange\Commands;

use App\User;
use App\Affiliation;
use App\StreamError;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\DataExchange\Notifications\StreamErrorNotification;

class CreateNotificationsForStreamErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dx:notify-errors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends notifications for errors resulting from the streaming service integeration.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $admins = User::role('admin')->get();

        $errors = StreamError::unsent()
            ->with(['geneModel', 'diseaseModel', 'moiModel'])
            ->get();

        if ($errors->isEmpty()) { return; }

        $groupedErrors = $errors->groupBy('affiliation_id');
        $affiliationIds = $groupedErrors->keys()->filter()->values()->all();
        $affiliationsByClingenId = Affiliation::with(['expertPanel.coordinators'])
            ->whereIn('clingen_id', $affiliationIds)
            ->get()
            ->keyBy('clingen_id');

        $parentLikeClingenIds = collect($affiliationIds)
            ->filter(fn ($id) => str_starts_with((string) $id, '1'))
            ->values();

        $parentLocalIds = $parentLikeClingenIds
            ->map(fn ($clingenId) => $affiliationsByClingenId->get($clingenId))
            ->filter()
            ->pluck('id')
            ->values()
            ->all();

        $childrenByParentId = collect();
        if (!empty($parentLocalIds)) {
            $childrenByParentId = Affiliation::with(['expertPanel.coordinators'])
                ->whereIn('parent_id', $parentLocalIds)
                ->get()
                ->groupBy('parent_id');
        }

        $unmatched = collect();

        foreach ($groupedErrors as $affiliationId => $groupErrors) {
            $coordinators = collect();

            $isParentLike = str_starts_with((string) $affiliationId, '1');

            if ($isParentLike) {
                $parentAff = $affiliationsByClingenId->get($affiliationId);

                if ($parentAff) {
                    $children = $childrenByParentId->get($parentAff->id, collect());

                    $coordinators = $children
                        ->flatMap(fn ($child) => optional($child->expertPanel)->coordinators ?? collect())
                        ->unique('id')
                        ->values();
                }
            } else {
                $aff = $affiliationsByClingenId->get($affiliationId);

                if ($aff && $aff->expertPanel) {
                    $coordinators = $aff->expertPanel->coordinators ?? collect();
                }
            }

            if ($coordinators->isNotEmpty()) {
                Notification::send($coordinators, new StreamErrorNotification($groupErrors));
            } else {
                $unmatched = $unmatched->merge($groupErrors);
            }
        }

        if ($unmatched->isNotEmpty()) {
            Notification::send($admins, new StreamErrorNotification($unmatched));
        }

        $errors->each->markSent();

        return 0;
    }


}

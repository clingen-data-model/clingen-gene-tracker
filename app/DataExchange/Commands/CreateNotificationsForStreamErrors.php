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
    protected $description = 'Sends notifications for errors resulting from the streaming service integration.';

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
        $groupedErrors = StreamError::unsent()
                            ->with('geneModel', 'diseaseModel', 'moiModel')
                            ->get()
                            ->groupBy('affiliation_id');
        $affiliations = Affiliation::with([
            'expertPanel.coordinators',
            'children.expertPanel.coordinators'
        ])->get()->keyBy('clingen_id');
        $admins = User::role('admin')->get();
        $groupedErrors->each(function ($errors, $affiliation_id) use ($affiliations, $admins) {
            $affiliation = $affiliations->get($affiliation_id);
            if (!$affiliation) {
                // admins get messages if there is no associated affiliation in the database
                Notification::send($admins, new StreamErrorNotification($errors));
                return;
            }
            // We have to look at the expert panel coordinators for the affiliation and any child affiliations,
            // since the streaming service errors are associated with the top-level affiliation, but the coordinators are
            // only associated with the expert panels as "child" affiliations. For current messages, we would actually
            // *only* need to look for coordinators of child affiliations, but it is safer to just check both levels
            // in case of any future changes to the affiliation structure of affiliations. See GT-72 for discussion.
            $coordinators = $affiliation->expertPanel?->coordinators ?? collect();
            foreach ($affiliation->children as $childAffiliation) {
                $coordinators = $coordinators->merge($childAffiliation->expertPanel?->coordinators ?? collect());
            }
            $coordinators = $coordinators->unique('id');
            if ($coordinators->isEmpty()) {
                // admins get messages if there are errors for an affiliation with no associated coordinators
                Notification::send($admins, new StreamErrorNotification($errors));
            } else {
                Notification::send($coordinators, new StreamErrorNotification($errors));
            }
        });

        $groupedErrors->flatten()->each->markSent();
    }
}

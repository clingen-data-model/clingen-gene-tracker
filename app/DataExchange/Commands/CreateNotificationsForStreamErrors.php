<?php

namespace App\DataExchange\Commands;

use App\Affiliation;
use App\DataExchange\Notifications\StreamErrorNotification;
use App\StreamError;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

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
     */
    public function handle(): void
    {
        $groupedErrors = StreamError::unsent()
            ->with('geneModel', 'diseaseModel', 'moiModel')
            ->get()
            ->groupBy('affiliation_id');
        $affiliations = Affiliation::with('expertPanel')->get()->keyBy('clingen_id');
        $groupedErrors->each(function ($errors, $affiliation_id) use ($affiliations) {
            $affiliation = $affiliations->get($affiliation_id);
            if (! $affiliation) {
                return;
            }
            if (! $affiliation->expertPanel || $affiliation->expertPanel->coordinators->count() == 0) {
                Notification::send(User::role('admin')->get(), new StreamErrorNotification($errors));

                return;
            }
            Notification::send($affiliation->expertPanel->coordinators, new StreamErrorNotification($errors));
        });

        $groupedErrors->flatten()->each->markSent();
    }
}

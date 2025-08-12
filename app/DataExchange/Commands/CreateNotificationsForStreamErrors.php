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
        $allAffiliations = Affiliation::with(['expertPanel.coordinators'])->get();
        $admins = User::role('admin')->get();

        $allCoordinators = $allAffiliations
            ->pluck('expertPanel')
            ->filter()
            ->flatMap->coordinators
            ->unique('id')
            ->values();
        
        $groupedErrors = StreamError::unsent()
            ->with(['geneModel', 'diseaseModel', 'moiModel'])
            ->get()
            ->groupBy('affiliation_id');

        $recipients = $allCoordinators->isNotEmpty() ? $allCoordinators : $admins;

        $groupedErrors->each(function ($errors) use ($recipients) {
            Notification::send($recipients, new StreamErrorNotification($errors));
        });
        
        $groupedErrors->flatten()->each->markSent();
    }

}

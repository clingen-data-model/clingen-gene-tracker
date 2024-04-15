<?php

namespace App\Console\Commands;

use App\Notifications\CurationNotificationsDigest;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendNotificationDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends digests of curation notifications as email; marks notifications read_at to time sent.';

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
        Log::info('Sending notification digests.');
        // Make sure notifications have been created for all unsent streaming service errors
        $this->call('dx:notify-errors');

        $hasUnread = User::has('unreadNotifications');

        $bar = $this->output->createProgressBar($hasUnread->count());
        
        $hasUnread->with('unreadNotifications')->each(function ($user) use ($bar) {
            $groupedNotifications =  $user->unreadNotifications
                                        ->groupBy('type')
                                        ->map(function ($group, $class) {
                                            return $class::getValidUnique($group);
                                        })->filter(function ($group) {
                                            return $group->count() > 0;
                                        });

            if ($groupedNotifications->count() == 0) {
                return;
            }
            
            $user->notify(new CurationNotificationsDigest($groupedNotifications));
            $user->unreadNotifications
                ->each
                ->update([
                    'read_at' => Carbon::now()
                ]);
            $bar->advance();
        });
        $bar->finish();
        Log::info('Sent notification digests.');
    }
}

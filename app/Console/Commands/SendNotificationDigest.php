<?php

namespace App\Console\Commands;

use App\Notifications\CurationNotificationsDigest;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DigestRecipients;

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
        $additionalEmails = DigestRecipients::normalize(config('notifications.digest_additional_emails', []));
        $pendingRead = collect();
        Log::info('Sending notification digests.');
        // Make sure notifications have been created for all unsent streaming service errors
        $this->call('dx:notify-errors');

        $hasUnread = User::has('unreadNotifications');

        $bar = $this->output->createProgressBar($hasUnread->count());

        // Aggregate only notifications that pass the existing per-user filtering.
        $globalNotifications = collect();
        
        $hasUnread->with('unreadNotifications')->each(function ($user) use ($bar, $globalNotifications, $additionalEmails, $pendingRead) {
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

            if ($additionalEmails !== []) {
                // Deduplicate before marking the source records read.
                foreach ($groupedNotifications as $type => $notifications) {
                    $existing = $globalNotifications->get($type, collect());

                    $globalNotifications->put(
                        $type,
                        $existing->concat($notifications)
                            ->unique(fn ($notification) => \App\Notifications\DigestEventIdentity::key($notification))
                            ->values()
                    );
                }
            }

            $email = strtolower(trim($user->email));
            if (in_array($email, $additionalEmails, true)) {
                $pendingRead->put($email, $pendingRead->get($email, collect())->concat($user->unreadNotifications));
                $bar->advance();
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

        // Overlapping users are marked read only after their combined mail succeeds.
        if ($globalNotifications->isNotEmpty()) {
            foreach ($additionalEmails as $email) {
                Notification::route('mail', $email)
                    ->notify(new CurationNotificationsDigest($globalNotifications));
                $pendingRead->get($email, collect())->each->update(['read_at' => Carbon::now()]);
            }
        }

        $bar->finish();
        Log::info('Sent notification digests.');
    }
}

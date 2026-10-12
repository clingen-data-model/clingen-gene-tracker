<?php

namespace App\Console\Commands;

use App\Notifications\CurationNotificationsDigest;
use App\Notifications\DigestRecipients;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
        $recipients = DigestRecipients::normalize(config('notifications.curation_digest_additional_emails', ''));
        $combined = collect();
        $pendingRead = collect();
        Log::info('Sending notification digests.');
        // Make sure notifications have been created for all unsent streaming service errors
        $this->call('dx:notify-errors');

        $hasUnread = User::has('unreadNotifications');

        $bar = $this->output->createProgressBar($hasUnread->count());
        
        // Keyset iteration remains stable as unread records are marked read.
        $hasUnread->with('unreadNotifications')->eachById(function ($user) use ($bar, $recipients, &$combined, &$pendingRead) {
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
            
            // Capture eligible snapshots before marking source records read.
            if ($recipients) {
                foreach ($groupedNotifications as $class => $group) {
                    $combined->put($class, $combined->get($class, collect())->concat($group));
                }
            }
            if (in_array(strtolower(trim($user->email)), $recipients, true)) {
                $pendingRead = $pendingRead->concat($user->unreadNotifications);
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
        $bar->finish();

        if ($recipients && $combined->isNotEmpty()) {
            $combined = $combined->map(fn ($group, $class) => $class::getValidUnique($group))
                ->filter(fn ($group) => $group->isNotEmpty());
            if ($combined->isNotEmpty()) {
                foreach ($recipients as $email) {
                    try {
                        Notification::route('mail', $email)->notify(new CurationNotificationsDigest($combined));
                    } catch (\Throwable $exception) {
                        Log::error('Failed to send combined curation digest.', ['email' => $email, 'exception' => $exception]);
                        throw $exception;
                    }
                }
                $pendingRead->each->update(['read_at' => Carbon::now()]);
            }
        }
        Log::info('Sent notification digests.');
    }
}

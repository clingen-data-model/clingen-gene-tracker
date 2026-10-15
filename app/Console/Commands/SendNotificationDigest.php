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
        Log::info('Sending notification digests.');
        // Make sure notifications have been created for all unsent streaming service errors
        $this->call('dx:notify-errors');

        $digests = collect();
        User::has('unreadNotifications')->with('unreadNotifications')->eachById(function ($user) use ($digests) {
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
            $digests->put($user->id, ['user' => $user, 'groups' => $groupedNotifications]);
        });

        // Nothing is marked read until the combined digest is out, so a rerun after its failure rebuilds it in full.
        if ($recipients) {
            $delivered = $this->sendCombinedDigest($recipients, $digests);
            $digests = $digests->reject(function ($digest) use ($recipients, $delivered) {
                $email = strtolower(trim($digest['user']->email));
                if (!in_array($email, $recipients, true)) {
                    return false;
                }
                if (in_array($email, $delivered, true)) {
                    $this->markRead($digest['user']);
                }
                return true;
            });
        }

        $bar = $this->output->createProgressBar($digests->count());
        $digests->each(function ($digest) use ($bar) {
            $digest['user']->notify(new CurationNotificationsDigest($digest['groups']));
            $this->markRead($digest['user']);
            $bar->advance();
        });
        $bar->finish();
        Log::info('Sent notification digests.');
    }

    private function sendCombinedDigest(array $recipients, $digests): array
    {
        // Groups were validated per user; only cross-user duplicates remain.
        $combined = $digests->pluck('groups')
            ->reduce(function ($carry, $groups) {
                foreach ($groups as $class => $group) {
                    $carry->put($class, $carry->get($class, collect())->concat($group));
                }
                return $carry;
            }, collect())
            ->map(fn ($group, $class) => $class::getUnique($group))
            ->filter(fn ($group) => $group->isNotEmpty());

        if ($combined->isEmpty()) {
            return [];
        }

        $delivered = [];
        $lastException = null;
        foreach ($recipients as $email) {
            try {
                Notification::route('mail', $email)->notify(new CurationNotificationsDigest($combined));
                $delivered[] = $email;
            } catch (\Throwable $exception) {
                Log::error('Failed to send combined curation digest.', ['email' => $email, 'exception' => $exception]);
                $lastException = $exception;
            }
        }
        // Total failure (e.g. mail transport down) stops the run before anything is marked read.
        if (empty($delivered)) {
            throw $lastException;
        }

        return $delivered;
    }

    private function markRead(User $user): void
    {
        $user->unreadNotifications->each->update(['read_at' => Carbon::now()]);
    }
}

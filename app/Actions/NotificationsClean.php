<?php

namespace App\Actions;

use Carbon\Carbon;
use App\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsCommand;
/**
 * Clean the notifications table
 * 
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
class NotificationsClean
{
    use AsCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $commandSignature = 'notifications:clean {--unread : Delete read AND unread notifications} {--created-before= : Only delete notifications created before a given date} {--read-before= : Only delete notifications read before a given date} {--f|force : Do not ask for confirmation in production} {--dry-run : Print number of notifications to delete; do not delete.}';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(
        bool $deleteUnread = false, 
        ?Carbon $createdBefore = null, 
        ?Carbon $readBefore = null, 
        bool $dryRun = false
    )
    {
        $query = Notification::query();
        
        if (!$deleteUnread) {
            $query->read();
        }
        
        $createdBefore = $createdBefore ?? Carbon::now()->subDays(30);

        $query->where('created_at', '<', $createdBefore);
        
        if ($readBefore) {
            $query->where('created_at', '<', $readBefore);
        }

        if ($dryRun) {
            return 'Would delete '.$query->count().' notifications';
        }
        
        $count = $query->count();
        $query->get()->each->delete();
        
        Log::info('Cleaned notifications table.');
        return $count.' notifications cleaned.';
    }

    public function asCommand(Command $command): void
    {
        if (app()->environment('production') && !$command->option('force')) {
            if (!$command->confirm('You are about to clear notifications in a PRODUCTION environment.  This cannot be undone.  Are you sure you want to continue?')) {
                $command->info('notificaions:clear was cancelled.');
                return;
            }
        }
        $response = $this->handle(
            deleteUnread: $command->option('unread'), 
            createdBefore: $command->option('created-before') ? Carbon::parse($command->option('created-before')) : null,
            readBefore: $command->option('read-before') ? Carbon::parse($command->option('read-before')) : null,
            dryRun: $command->option('dry-run')
        );
        if (!$command->option('quiet')) {
            $command->info($response);
        }
    }

    public function getCommandDescription()
    {
        return 'Cleans read notifications.';
    }
    
    
}

<?php

namespace App\Console\Commands;

use App\User;
use App\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Notifications\Curations\PhenotypeAddedForCurationNotification;

class DeleteDuplicateNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:delete-duplicates  {--chunk-size=1000 : size of chunk to work with}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Proactively delete duplicate notifications to prevent storage/memory overflows.';

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
     * @return int
     */
    public function handle()
    {
        $this->info('DeleteDuplicateNotifications...');
        $this->deleteEachDuplicate();
    }

    public function deleteEachDuplicate()
    {
        $this->info('using deleteEachDuplicate...');
        
        $itc = 0;
        $uniqueRecords = collect();
        while ($uniqueRecords->flatten()->count() < Notification::count()) {
          $itc++;
          $notifications_processed = 0;
          $this->info('start iteration '.$itc);
          Notification::query()
            ->chunk($this->option('chunk-size'), function ($chunk) use ($uniqueRecords, &$notifications_processed, &$deletes) {
                $progress = $this->output->createProgressBar($this->option('chunk-size'));
                $chunk->each(function ($notification) use ($uniqueRecords, &$deletes, $progress, &$notifications_processed) {
                    $type = $notification->type;
                    $uniqueString = $notification->type::uniqueStringForItem($notification);
                    $uniqueStringWithUserId = $uniqueString.'-user:'.$notification->notifiable_id;
                    $notifications_processed++;
                    
                    if (!$uniqueRecords->get($type)) {
                        $uniqueRecords->put($type, collect());
                    }

                    
                    $match = $uniqueRecords->get($type)->get($uniqueStringWithUserId);
                    if ($match && $match != $notification->id) {
                        $notification->delete();
                        $deletes++;
                        $progress->advance();
                        return;
                    }

                    $uniqueRecords->get($type)->put($uniqueStringWithUserId, $notification->id);
                    $progress->advance();
                });
                $progress->finish();
                $this->info("\n");
                $this->info('processed '.$notifications_processed.' of '.Notification::count());
                $this->info('Total of '.$deletes.' duplicates deleted');
                $this->info($uniqueRecords->flatten()->count().' notifications in uniques using '.round((memory_get_usage()/1000000), 2).'MB');
            });
        }
    }
    
}

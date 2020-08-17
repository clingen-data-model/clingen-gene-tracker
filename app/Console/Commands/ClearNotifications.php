<?php

namespace App\Console\Commands;

use App\Notification;
use Illuminate\Console\Command;

class ClearNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        if (app()->environment('production')) {
            if (!$this->confirm('You are about to clear notifications in a PRODUCTION environment.  This cannot be undone.  Are you sure you want to continue?')) {
                $this->info('notificaions:clear was cancelled.');
                return;
            }
        }
        Notification::all()->each->delete();
    }
}

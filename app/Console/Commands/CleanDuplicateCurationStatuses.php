<?php

namespace App\Console\Commands;

use App\Services\Utilities\CleanDuplicateCurationStatuses as Cleaner;
use Illuminate\Console\Command;

class CleanDuplicateCurationStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:clean-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes duplicate statuses';

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
        $cleaner = new Cleaner();
        $cleaner->clean();
    }
}

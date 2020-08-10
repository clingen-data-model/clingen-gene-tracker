<?php

namespace App\Console\Commands;

use App\Email;
use Illuminate\Console\Command;

class ClearEmailLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:clear-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears Email db log table';

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
        Email::all()->each->delete();
    }
}

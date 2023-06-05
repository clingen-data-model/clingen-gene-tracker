<?php

namespace App\Console\Commands\HealthCheck;

use Illuminate\Console\Command;

class DbHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'healthcheck:db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that the database is up and running.';

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
        try {
            \DB::connection()->getPdo();
            $this->info('Database is up and running.');
        } catch (\Exception $e) {
            $this->error('Database is down.');
            return 1;
        }
    }
}

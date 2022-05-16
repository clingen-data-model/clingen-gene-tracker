<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\PopulateMimNames;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        PopulateMimNames::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('hgnc:update-data')
            ->dailyAt("01:00:00");
        $schedule->command('mondo:update-data')
            ->dailyAt("O2:00:00");
        $schedule->command('omim:update-data')
            ->dailyAt("03:00:00")
            ->after(function () {
                Artisan::call('omim:check-moved-and-removed');
            });
        
        if (config('dx.consume', true)) {
            $schedule->command('gci:consume')
                ->hourly();
            $schedule->command('dx:consume-mondo')
                ->hourly();
        }

        $schedule->command('send-notifications')
            ->weekly()->mondays()->at('6:00');

        $schedule->command('gci:affiliations-update')
            ->everySixHours();

        $schedule->command('notifications:clean -f -q')
            ->monthly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load([__DIR__.'/Commands', app_path('Hgnc/Artisan')]);

        require base_path('routes/console.php');
    }
}

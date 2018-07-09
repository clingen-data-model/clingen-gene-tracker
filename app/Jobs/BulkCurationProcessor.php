<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BulkCurationProcessor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $excelPath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($excelPath)
    {
        //
        $this->excelPath = $excelPath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        dd('BulkCurationProcessor');
    }
}

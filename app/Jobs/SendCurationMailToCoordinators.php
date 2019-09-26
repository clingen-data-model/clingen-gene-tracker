<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendCurationMailToCoordinators implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $curation;

    protected $mailClass;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($curation, $mailClass)
    {
        //
        $this->curation = $curation;
        $this->mailClass = $mailClass;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->curation->expertPanel->coordinators as $coordinator) {
            \Mail::to($coordinator->email)->send(new $this->mailClass($this->curation));
        }
    }
}

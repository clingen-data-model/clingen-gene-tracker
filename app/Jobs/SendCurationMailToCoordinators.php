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

    protected $additional;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($curation, $mailClass, ...$additional)
    {
        //
        $this->curation = $curation;
        $this->mailClass = $mailClass;
        $this->additional = $additional;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $curatorEmails = $this->curation->expertPanel->coordinators->pluck('email');
        \Mail::to($curatorEmails)
            ->send(new $this->mailClass($this->curation, ...$this->additional));
    }
}

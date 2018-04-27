<?php

namespace App\Jobs\Topics;

use App\Phenotype;
use App\Topic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncPhenotypes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $topic;
    protected $mimNumbers;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Topic $topic, $mimNumbers)
    {
        $this->topic = $topic;
        $this->mimNumbers = collect($mimNumbers);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->mimNumbers && $this->mimNumbers->count() > 0) {
            return;
        }
        $phenotypes = Phenotype::whereIn('mim_number', $this->mimNumbers)->get();
        $newMims = $this->mimNumbers->diff($phenotypes->pluck('mim_number'));
        $newMims->each(function ($mimNumber) use ($phenotypes) {
            $phenotypes->push(Phenotype::create(['mim_number'=>$mimNumber]));
        });
        $this->topic->phenotypes()->sync($phenotypes->pluck('id'));
    }
}

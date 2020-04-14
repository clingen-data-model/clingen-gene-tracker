<?php

namespace App\Jobs;

use App\StateVariable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SetState implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $name;

    protected $value;

    protected $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($name, $value, $type = 'integer')
    {
        //
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        StateVariable::updateOrCreate(
            ['name' => $this->name],
            [
                'value' => $this->value,
                'type' => $this->type
            ]
        );
    }
}

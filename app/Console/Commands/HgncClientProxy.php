<?php

namespace App\Console\Commands;

use App\Contracts\HgncClient;
use Illuminate\Console\Command;

class HgncClientProxy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:hgnc {methodsig : method and args in method:arg1,arg2 form to run against HGNC client}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a method from the HGNC client';

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
        $client = app()->make(HgncClient::class);
        ['method' => $method, 'args' => $args] = $this->parseSignature($this->argument('methodsig'));

        if (!method_exists($client, $method)) {
            $this->error('Method '.$method.'does not exist on the HGNC client');

            return;
        }

        try {
            $result = call_user_func_array([$client, $method], $args);
            echo json_encode($result, JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function parseSignature($signature)
    {
        [$method, $args] = explode(':', $signature);
        $args = explode(',', $args);

        return compact('method', 'args');
    }
}

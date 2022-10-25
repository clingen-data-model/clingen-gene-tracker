<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExportConfigsToJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:export {--configs= : comma separated list of configs to export}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export configs as json for use in other contexts.';

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
        $configs = ($this->option('configs'))
                        ? explode(',', $this->option('configs'))
                        : ['expert-panels', 'next_actions', 'mail.from', 'groups', 'system', 'feedback', 'submissions', 'app.features', 'documents.types'];

        $this->info('Exporting '.implode(', ', $configs).' to JSON.');

        $export = [];

        foreach ($configs as $config) {
            $config = trim($config);
            $configName = Str::camel(preg_replace('/\./', '-', $config));

            $camelCased = [];
            foreach (config($config) as $key => $value) {
                $camelCased[Str::camel($key)] = $value;
            }
            $export[$configName] = $camelCased;
        }

        file_put_contents(base_path('resources/app/src/configs.json'), json_encode($export, JSON_PRETTY_PRINT));
    }
}

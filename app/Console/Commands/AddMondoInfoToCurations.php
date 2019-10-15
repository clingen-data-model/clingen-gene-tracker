<?php

namespace App\Console\Commands;

use App\Contracts\MondoClient;
use App\Curation;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curation\AugmentWithMondoInfo;
use Illuminate\Console\Command;

class AddMondoInfoToCurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:add-mondo-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Mondo Info to existing curations';

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
    public function handle(MondoClient $mondoClient)
    {
        $curations = Curation::whereNotNull('mondo_id')->get();
        $bar = $this->output->createProgressBar($curations->count());

        $curations->groupBy('numericMondoId')
            ->each(function ($group, $mondoId) use ($bar, $mondoClient) {
                try {
                    $mondoRecord = $mondoClient->fetchRecord($mondoId);
                } catch (HttpNotFoundException $e) {
                    dump($e->getMessage());
                    $bar->advance($group->count());
                    return;
                }
                \DB::table('curations')
                    ->where('mondo_id', 'MONDO:'.$mondoId)
                    ->update(['mondo_name' => $mondoRecord->label]);
                $bar->advance($group->count());
                // $group->each(function ($curation) use ($bar) {
                //     $curation->update([
                //         ''
                //     ]);
                //     $bar->advance();
                // });
            });

        // $curations->each(function ($curation) use ($bar) {
        //     try {
        //         AugmentWithMondoInfo::dispatch($curation);
        //     } catch (HttpNotFoundException $e) {
        //         \Log::warning('Mondo id '.$curation->mondo_id.' was not found via MonDO API');
        //         if (app()->environment('local', 'testing')) {
        //             dump($e->getMessage());
        //         }
        //     }
        //     $bar->advance();
        // });
    }
}

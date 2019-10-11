<?php

namespace App\Console\Commands;

use App\Contracts\HgncClient;
use App\Curation;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curation\AugmentWithHgncInfo;
use Illuminate\Console\Command;

class AddHgncInfoToCurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:add-hgnc-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds HGNC name and id to existing curation records';

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
    public function handle(HgncClient $hgncClient)
    {
        $curations = Curation::all();
        $bar = $this->output->createProgressBar($curations->count());

        $curations->groupBy('gene_symbol')
            ->each(function ($group, $geneSymbol) use ($bar, $hgncClient) {
                try {
                    $symbolRecord = $hgncClient->fetchGeneSymbol($geneSymbol);
                } catch (HttpNotFoundException $th) {
                    try {
                        $symbolRecord = $hgncClient->fetchPreviousSymbol($geneSymbol);
                    } catch (\Throwable $th) {
                        dump($th->getMessage());
                        $bar->advance($group->count());
                        return;
                    }
                }

                \DB::table('curations')
                    ->whereIn('id', $group->pluck('id')->toArray())
                    ->update([
                        'gene_symbol' => $symbolRecord->symbol,
                        'hgnc_name' => $symbolRecord->name,
                        'hgnc_id' => $symbolRecord->hgnc_id
                        ]);
                $bar->advance($group->count());

                // $group->each(function ($curation) use ($bar, $symbolRecord) {
                //     $curation->update([
                //         'gene_symbol' => $symbolRecord->symbol,
                //         'hgnc_name' => $symbolRecord->name,
                //         'hgnc_id' => $symbolRecord->hgnc_id
                //     ]);
                //     $bar->advance();
                // });
            });

        // $curations->each(function ($curation) use ($bar) {
        //     try {
        //         AugmentWithHgncInfo::dispatch($curation);
        //     } catch (HttpNotFoundException $e) {
        //         \Log::warning($e->getMessage());
        //         dump($e->getMessage());
        //     }
        //     $bar->advance();
        // });
    }
}

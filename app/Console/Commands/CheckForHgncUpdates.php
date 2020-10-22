<?php

namespace App\Console\Commands;

use App\Curation;
use App\Hgnc\HgncRecord;
use App\Hgnc\HgncClientContract;
use App\Exceptions\ApiServerErrorException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curations\AugmentWithHgncInfo;
use App\Jobs\SendCurationMailToCoordinators;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Curations\HgncIdNotFoundNotification;
use App\Notifications\Curations\GeneSymbolUpdated;

class CheckForHgncUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:check-hgnc-updates {--symbol= : gene symbol to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks HGNC api for all gene symbols, updates curation if gene symbol changed and notifies coordinator.';

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
        \Log::info('Checking HGNC for updates.');
        $query = Curation::query()
            ->with('expertPanel');
        if ($this->option('symbol')) {
            $query->where('gene_symbol', $this->option('symbol'));
        }

        $query->get()
            ->groupBy('hgnc_id')
            ->each(function ($curations, $hgncId) use ($hgncClient) {
                $this->reconcileSymbol($hgncClient, $curations, $hgncId);
            });
    }

    private function reconcileSymbol(HgncClient $client, Collection $curations, $hgncId)
    {
        if (empty($hgncId)) {
            $this->tryFillingHgncId($curations);
            return;
        }
        try {
            $hgncRecord = $client->fetchHgncId($hgncId);
            $curations->each(function ($curation) use ($hgncRecord) {
                if ($curation->gene_symbol != $hgncRecord->symbol) {
                    $this->updateGeneSymbol($curation, $hgncRecord);
                }
            });
        } catch (HttpNotFoundException $e) {
            $this->tryFillingHgncId($curations);
        }
    }

    private function updateGeneSymbol(Curation $curation, HgncRecord $hgncRecord)
    {
        $oldGeneSymbol = $curation->gene_symbol;
        $curation->update(['gene_symbol' => $hgncRecord->symbol]);
        $curation->expertPanel->coordinators->each(function ($coordinator) use ($curation, $oldGeneSymbol) {
            $coordinator->notify(new GeneSymbolUpdated($curation, $oldGeneSymbol));
        });
    }

    private function tryFillingHgncId($curations)
    {
        $curations->each(function ($curation) {
            try {
                AugmentWithHgncInfo::dispatch($curation);
            } catch (HttpNotFoundException $e) {
                NotifyCoordinatorsAboutCuration::dispatch($curation, HgncIdNotFoundNotification::class);
            } catch (ApiServerErrorException $e) {
                report($e);
            }
        });
    }
}

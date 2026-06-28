<?php

namespace App\Console\Commands;

use App\Phenotype;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneOutdatedPhenotypeLabels extends Command
{
    protected $signature = 'omim:prune-outdated-labels
                            {--days=30 : Minimum number of days from when the label was obsoleted}
                            {--dry-run : Show records without deleting them}';

    protected $description = 'Remove phenotype labels no longer in genemap2 and unused by curations';

    public function handle()
    {
        $days = max(1, (int) $this->option('days'));

        $phenotypes = Phenotype::query()->whereNotNull('label_obsolete_at')
            ->where('label_obsolete_at', '<=', now()->subDays($days))
            ->whereDoesntHave('curations')
            ->get();

        if ($phenotypes->isEmpty()) {
            $this->info('No unused outdated phenotype labels found.');
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->info("Would prune {$phenotypes->count()} phenotype label(s).");
            foreach ($phenotypes as $phenotype) {
                $this->line("{$phenotype->id}: {$phenotype->mim_number} — {$phenotype->name}");
            }
            return 0;
        }

        DB::transaction(function () use ($phenotypes) {
            foreach ($phenotypes as $phenotype) {
                $phenotype->genes()->detach();
                $phenotype->delete();
            }
        });

        Log::info('Pruned unused outdated OMIM phenotype labels.', ['count' => $phenotypes->count()]);
        $this->info("Pruned {$phenotypes->count()} phenotype label(s).");
        return 0;
    }
}
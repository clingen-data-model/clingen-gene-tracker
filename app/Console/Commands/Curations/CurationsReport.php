<?php

namespace App\Console\Commands\Curations;

use Illuminate\Console\Command;
use App\Curation;

class CurationsReport extends Command
{
    protected $signature = 'curations:report';

    protected $description = 'Generate a JSON report of published curations';

    public function handle()
    {
        foreach (Curation::with(['phenotypes', 'curationType', 'currentStatus', 'expertPanels', 'rationales', 'moi'])
                ->where('curation_status_id', '=', 9)
                ->lazy(10) as $curation) {
            $output = $curation->only(['gene_symbol', 'hgnc_name', 'hgnc_id', 'rationale_notes', 'curation_notes']);
            $output['moi'] = $curation->moi?->name;
            $output['disease_mondo'] = $curation->disease?->mondo_id;
            $output['disease_name'] = $curation->disease?->name;
            $output['rationales'] = $curation->rationales->pluck('name');
            $output['type'] = $curation->curationType?->name;
            $output['phenotypes'] = $curation->phenotypes->pluck('name', 'mim_number');
            $output['expertPanels'] = $curation->expertPanels->pluck('name');
            $this->info(json_encode($output, JSON_UNESCAPED_SLASHES));
        }
    }
}

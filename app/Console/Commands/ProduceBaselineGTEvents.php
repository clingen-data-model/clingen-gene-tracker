<?php

namespace App\Console\Commands;

use App\Curation;
use Ramsey\Uuid\Uuid;
use App\StreamMessage;
use Illuminate\Console\Command;

class ProduceBaselineGTEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gci:produce-baseline {--limit= : number of messags to produce} {--topic=test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Produce events that provide baseline state of pre-curations for gci';

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
        $query = Curation::query()
                    ->with(['curationStatuses', 'expertPanel', 'expertPanel.affiliation', 'expertPanel.affiliation.parent', 'modeOfInheritance', 'curationType', 'phenotypes'])
                    ->whereNull('gdm_uuid');

        if ($this->getLimit()) {
            $query->limit($this->getLimit());
        }

        $query->whereNotNull('mondo_id')
            ->whereNotNull('moi_id');

        $curations = $query->get();

        $bar = $this->output->createProgressBar($curations->count());

        $messages = $curations->map(function ($curation) use ($bar) {
            $bar->advance();
            if (!$curation->currentStatus) {
                return;
            }
            $message = new StreamMessage([
                        'topic' => $this->option('topic'),
                        'message' => [
                            'key' => Uuid::uuid4()->toString(),
                            'event_type' => 'baseline_state',
                            'schema_version' => 1,
                            'data' => [
                                'id' => $curation->id,
                                'uuid' => $curation->uuid,
                                'gene_symbol' => $curation->gene_symbol,
                                'hgnc_id' => 'HGNC:'.$curation->hgnc_id,
                                'mondo_id' => $curation->mondo_id,
                                'mode_of_inheritance' => $curation->modeOfInheritance->hp_id,
                                'group' => [
                                    'name' => $curation->expertPanel->name,
                                    'id' => $curation->expertPanel->id,
                                    'affiliation_id' => ($curation->expertPanel->affiliation)
                                                            ? $curation->expertPanel->affiliation->clingen_id
                                                            : null
                                ],
                                'curation_status' => [
                                    'name' => $curation->currentStatus->name,
                                    'id' => $curation->currentStatus->id
                                ],
                                'gdm_uuid' => $curation->gdm_uuid
                            ]
                        ]
                    ]);
            $message->save();
            return $message;
        });
    }

    private function getLimit()
    {
        if ($this->hasOption('limit')) {
            return $this->option('limit');
        }

        return null;
    }
}

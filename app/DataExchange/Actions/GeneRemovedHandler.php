<?php

namespace App\DataExchange\Actions;

use App\Curation;
use App\IncomingStreamMessage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GeneRemovedHandler
{
    public function __construct(private ResolveGpmExpertPanel $resolveExpertPanel) {
    }

    public function handle(IncomingStreamMessage $incomingMessage, array $payload): void
    {
        DB::transaction(function () use ($payload) {
            $expertPanel = $this->resolveExpertPanel->handle($payload);
            $genes = data_get($payload, 'data.genes', []);
            if (empty($genes)) {
                throw new RuntimeException('The gene_removed event does not contain any genes.');
            }
            foreach ($genes as $gene) {
                $this->removeGeneCurations($expertPanel->id, $gene);
            }
        });
    }

    private function removeGeneCurations(int $expertPanelId, array $gene): void
    {
        $query = Curation::query()->where('expert_panel_id', $expertPanelId)->whereNotNull('incoming_stream_message_id');
        if (!empty($gene['hgnc_id'])) {
            $query->where('hgnc_id', $gene['hgnc_id']);
        } elseif (!empty($gene['gene_symbol'])) {
            $query->where('gene_symbol', $gene['gene_symbol']);
        } else {
            throw new RuntimeException('The removed gene is missing both hgnc_id and gene_symbol.');
        }
        $query->get()->each->delete();
    }
}
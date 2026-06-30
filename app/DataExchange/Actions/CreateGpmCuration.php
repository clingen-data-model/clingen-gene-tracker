<?php

namespace App\DataExchange\Actions;

use App\User;
use App\Curation;
use App\ExpertPanel;
use App\IncomingStreamMessage;
use InvalidArgumentException;

class CreateGpmCuration
{
    public function handle(
        ExpertPanel $expertPanel,
        IncomingStreamMessage $incomingMessage,
        array $gene,
        ?User $coordinator = null
    ): Curation {
        $geneSymbol = trim($gene['gene_symbol'] ?? '');

        if ($geneSymbol === '') {
            throw new InvalidArgumentException(
                'GPM gene is missing gene_symbol.'
            );
        }

        // Prevent only a replay of this exact DX message from creating the same gene twice.
         // A later GPM message may intentionally create another curation for the same gene and expert panel.
        return Curation::firstOrCreate(
            [
                'incoming_stream_message_id' => $incomingMessage->id,
                'expert_panel_id' => $expertPanel->id,
                'gene_symbol' => $geneSymbol,
            ],
            [
                'hgnc_id' => $gene['hgnc_id'] ?? null,
                'curator_id' => $coordinator?->id,
            ]
        );
    }
}
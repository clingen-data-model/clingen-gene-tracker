<?php

namespace App\DataExchange\Actions;

use App\IncomingStreamMessage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GenesAddedHandler
{
    public function __construct(private ResolveGpmExpertPanel $resolveExpertPanel, private CreateGpmCuration $createCuration) {
    }

    public function handle(IncomingStreamMessage $incomingMessage, array $payload): void
    {
        DB::transaction(function () use ($incomingMessage, $payload) {
            $expertPanel = $this->resolveExpertPanel->handle($payload);
            $coordinator = $expertPanel->coordinators()->first();
            $genes = data_get($payload, 'data.genes', []);
            if (empty($genes)) {
                throw new RuntimeException('The genes_added event does not contain any genes.');
            }
            if (!$coordinator) {
                throw new RuntimeException('GT could not find a Coordinator for expert panel ' . $expertPanel->name . '.');
            }
            foreach ($genes as $gene) {
                $this->createCuration->handle($expertPanel, $incomingMessage, $gene, $coordinator);
            }
        });
    }
}
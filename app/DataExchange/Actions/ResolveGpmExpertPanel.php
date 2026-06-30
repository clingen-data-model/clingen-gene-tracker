<?php

namespace App\DataExchange\Actions;

use App\ExpertPanel;
use RuntimeException;

class ResolveGpmExpertPanel
{
    public function handle(array $payload): ExpertPanel
    {
        $affiliationId = data_get($payload, 'data.group.expert_panel.affiliation_id');
        if (!$affiliationId) {
            throw new RuntimeException('The GPM event is missing the expert-panel affiliation ID.');
        }
        $expertPanel = ExpertPanel::findByAffiliationId($affiliationId);
        if (!$expertPanel) {
            throw new RuntimeException('GT expert panel was not found for affiliation ID '.$affiliationId.'.');
        }
        if ($expertPanel->affiliation?->type?->name !== 'gcep') {
            throw new RuntimeException('Affiliation ID '.$affiliationId.' does not belong to a GCEP.');
        }
        return $expertPanel;
    }
}
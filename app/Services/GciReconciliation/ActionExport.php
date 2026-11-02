<?php
namespace App\Services\GciReconciliation;

use Illuminate\Support\Str;

class ActionExport
{
    public function payload(iterable $rows): array
    {
        $eligible = $skipped = $items = $ids = [];
        foreach ($rows as $row) {
            if ($row->review_status !== 'REVIEWED' || !in_array($row->resolution, Decision::GCI_ACTIONS, true)) {
                $skipped[] = ['id' => $row->id, 'reason' => 'No reviewed GCI action decision.'];
            } elseif (!Str::isUuid($row->gci_gdm_uuid ?? '')) {
                $skipped[] = ['id' => $row->id, 'reason' => 'Missing or invalid GCI UUID.'];
            } else {
                $eligible[strtolower($row->gci_gdm_uuid)][] = $row;
            }
        }
        foreach ($eligible as $uuid => $group) {
            // Never send conflicting instructions for the same GCI record.
            if (count(array_unique(array_map(fn ($r) => $r->resolution, $group))) > 1) {
                foreach ($group as $row) $skipped[] = ['id' => $row->id, 'reason' => 'Conflicting reviewed actions for GCI UUID '.$uuid];
                continue;
            }
            $row = array_shift($group);
            $items[] = ['PK' => $row->gci_gdm_uuid, 'resolution' => $row->resolution];
            $ids[] = $row->id;
            foreach ($group as $duplicate) $skipped[] = ['id' => $duplicate->id, 'reason' => 'Duplicate GCI action already included.'];
        }
        return ['payload' => ['Items' => $items, 'Count' => count($items)], 'ids' => $ids, 'skipped' => $skipped];
    }
}

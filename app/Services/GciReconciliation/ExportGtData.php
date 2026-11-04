<?php
namespace App\Services\GciReconciliation;

use App\Curation;
use Illuminate\Database\Eloquent\Collection;

/** Request-scoped export lookups. Never retained between exports or review mutations. */
class ExportGtData
{
    private array $targets = [];
    private array $owners = [];

    public function __construct(iterable $rows)
    {
        $ids = $uuids = [];
        $manual = app(ManualTarget::class);
        foreach ($rows as $row) {
            $id = $manual->selected($row) ? $row->resolved_gt_curation_id : $row->gt_curation_id;
            if ($id) $ids[$id] = $id;
            if ($row->gci_gdm_uuid) $uuids[$row->gci_gdm_uuid] = $row->gci_gdm_uuid;
        }
        // Bound IN-list sizes; load only relations actually used by identity checks.
        foreach (array_chunk(array_values($ids), 500) as $chunk) {
            $targets = Curation::without('disease')->withTrashed()->with([
                'expertPanel.affiliation' => fn ($q) => $q->without('type'),
                'expertPanel.affiliation.parent' => fn ($q) => $q->without('type'),
            ])->whereIn('id', $chunk)->get();
            foreach ($targets as $target) $this->targets[$target->id] = $target;
        }
        foreach (array_chunk(array_values($uuids), 500) as $chunk) {
            $owners = Curation::without('disease')->withTrashed()->whereIn('gdm_uuid', $chunk)
                ->get(['id', 'gdm_uuid', 'curation_status_id', 'archived_at', 'deleted_at']);
            foreach ($owners as $owner) $this->owners[strtolower($owner->gdm_uuid)][] = $owner;
        }
    }

    public function target(?int $id): ?Curation
    {
        return $this->targets[$id] ?? null;
    }

    public function owners(?string $uuid): Collection
    {
        return new Collection($this->owners[strtolower((string) $uuid)] ?? []);
    }
}

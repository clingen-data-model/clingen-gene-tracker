<?php
namespace App\Services\GciReconciliation;

use Illuminate\Support\Str;
use RuntimeException;

class Selection
{
    public function query(?string $run, array $ids = [])
    {
        $query = Record::query();
        if ($run) {
            if (!Str::isUuid($run)) throw new RuntimeException('--run must be a reconciliation UUID.');
            $query->where('run_uuid', $run);
        }
        if ($ids) {
            foreach ($ids as $id) if (!ctype_digit((string) $id) || (int) $id < 1) throw new RuntimeException('--id must be a positive reconciliation row ID.');
            $query->whereIn('id', $ids);
            if ((clone $query)->count() !== count(array_unique($ids))) throw new RuntimeException('Some selected reconciliation rows do not exist in this selection.');
        }
        if ((clone $query)->distinct()->count('run_uuid') > 1) {
            throw new RuntimeException('Multiple reconciliation runs found. Select one with --run=<uuid> (available in row details or CLI reconciliation output).');
        }
        return $query->orderBy('id');
    }
}

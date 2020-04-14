<?php

namespace App\Services;

use App\CurationStatus;
use App\Exceptions\GciSyncException;

class GciStatusMap
{
    private $map;

    public function __construct()
    {
        $this->map = CurationStatus::all()->keyBy(function ($item) {
            return strtolower($item->name);
        });
        $this->map->put('created', $this->map['curation in progress']); // Confirm with Courtney
        $this->map->put('none', $this->map['uploaded']);
        $this->map->put('in progress', $this->map['curation in progress']);
        $this->map->put('approved', $this->map['curation approved']);
        $this->map->put('provisional', $this->map['curation provisional']);
        $this->map->put('provisionally approved', $this->map['curation provisional']);
        $this->map->put('provisionally_approved', $this->map['curation provisional']);
    }

    public function get($gciStatus)
    {
        $newStatus = $this->map->get($gciStatus);
        if (!$newStatus) {
            $newStatus = $this->map->get('Curation '.$gciStatus);
            if (!$newStatus) {
                throw new GciSyncException('Unknown status: '.$gciStatus);
            }
        }

        return $newStatus;
    }
}

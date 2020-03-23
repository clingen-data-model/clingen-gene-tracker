<?php

namespace App\Gci;

use App\Classification;
use App\Exceptions\GciSyncException;

class GciClassificationMap
{
    protected $map;

    public function __construct()
    {
        $this->map = Classification::all()->keyBy(function ($item) {
            return strtolower($item->name);
        });
        $this->map->put('no reported evidence', $this->map->get('no known disease relationship'));
    }

    public function get($key)
    {
        $classification = $this->map->get(strtolower($key));
        if (!$classification) {
            throw new GciSyncException('Unknown Classification: '.$key);
        }
        return $classification;
    }
}

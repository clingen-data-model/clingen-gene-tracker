<?php

namespace App;

final class HgncRecord extends ExternalServiceRecord
{
    public function getHgncId()
    {
        return (int)substr($this->attributes->hgnc_id, 5);
    }

    public function getGeneSymbol()
    {
        return $this->attributes->symbol;
    }

    public function hasPreviousSymbol()
    {
        return isset($this->attributes->prev_symbol);
    }

    public function getPreviousSymbol()
    {
        return $this->attributes->prev_symbol;
    }
}

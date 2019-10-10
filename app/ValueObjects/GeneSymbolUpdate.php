<?php

namespace App\ValueObjects;

use App\Contracts\GeneSymbolUpdate as GeneSymbolUpdateContract;

class GeneSymbolUpdate implements GeneSymbolUpdateContract
{
    protected $originalSymbol;

    protected $resolvedSymbol;

    public function __construct($originalSymbol, $resolvedSymbol)
    {
        $this->originalSymbol = $originalSymbol;
        $this->resolvedSymbol = $resolvedSymbol;
    }



    public function wasFound():bool
    {
        return !is_null($this->resolvedSymbol);
    }

    public function wasUpdated():bool
    {
        return $this->resolvedSymbol != $this->originalSymbol;
    }

    public function getNewSymbol():string
    {
        return $this->resolvedSymbol;
    }
    
    
}

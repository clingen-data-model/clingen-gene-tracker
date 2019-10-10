<?php
namespace App\Contracts;

interface GeneSymbolUpdate
{
    public function wasFound():bool;
    public function wasUpdated():bool;
    public function getNewSymbol():string;    
}
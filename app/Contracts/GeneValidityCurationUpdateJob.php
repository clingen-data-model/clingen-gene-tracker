<?php

namespace App\Contracts;

use App\Curation;
use App\Gci\GciMessage;
use App\Services\GciStatusMap;
use App\Gci\GciClassificationMap;

interface GeneValidityCurationUpdateJob
{
    public function __construct(GciStatusMap $statusMap, GciClassificationMap $classificationMap, Curation $curation, GciMessage $message);
    public function handle();
}

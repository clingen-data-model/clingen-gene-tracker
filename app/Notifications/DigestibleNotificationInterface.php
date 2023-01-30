<?php

namespace App\Notifications;

use Illuminate\Support\Collection;

interface DigestibleNotificationInterface
{
    public static function uniqueStringForItem($item):string;
    public static function getUnique(Collection $collection):Collection;
    public static function filterInvalid(Collection $collection):Collection;
    public static function getValidUnique(Collection $collection):Collection;
    public static function getDigestTemplate():string;
}
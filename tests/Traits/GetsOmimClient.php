<?php

namespace Tests\Traits;

use App\Clients\OmimClient;
use Tests\MocksGuzzleRequests;

/**
 * Includes private method getOmimClient
 */
trait GetsOmimClient
{
    use MocksGuzzleRequests;

    private function getOmimClient($responses)
    {
        $guzzleClient = $this->getGuzzleClient($responses, ['ApiKey' => config('app.omim_key')]);

        return new OmimClient($guzzleClient);
    }
}

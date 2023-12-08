<?php


namespace App\Services;

use Carbon\Carbon;

/**
* Cleans request input
*/
class RequestDataCleaner
{
    public function clean($data, $options)
    {
        $this->parseOptions($options);
        foreach ($data as $key => $value) {
            if (in_array($key, $this->dates)) {
                $data[$key] = Carbon::parse($value);
            }
        }

        return $data;
    }

    protected function parseOptions($options)
    {
        $this->dates = $options['dates'];
    }
}

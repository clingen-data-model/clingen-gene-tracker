<?php

namespace App\Rules;

use App\Clients\OmimClient;
use App\Contracts\OmimClient as OmimClientContract;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Validation\Rule;

class ValidOmimId implements Rule
{
    protected $omim;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(OmimClientContract $omimClient = null)
    {
        $this->omim = ($omimClient) ? $omimClient : new OmimClient();
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        try {
            $entries = $this->omim->getEntry($value);
            if (count($entries) > 0) {
                return true;
            }

            return false;
        } catch (ClientException $e) {
            if ($e->getCode() == 400) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This must be a valid MIM number.';
    }
}

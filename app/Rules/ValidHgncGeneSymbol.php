<?php

namespace App\Rules;

use App\Contracts\OmimClient;
use Illuminate\Contracts\Validation\Rule;

class ValidHgncGeneSymbol implements Rule
{
    protected $omim;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->omim = resolve(OmimClient::class);
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
        return $this->omim->geneSymbolIsValid($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ":input is not a valid HGNC gene symbol according to OMIM";
    }
}

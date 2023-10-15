<?php

namespace App\Rules;

use App\Gene;

class ValidHgncGeneSymbol implements ValidGeneSymbolRule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes(string $attribute, $value): bool
    {
        return Gene::findBySymbol($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return ':input is not a valid HGNC gene symbol.';
    }
}

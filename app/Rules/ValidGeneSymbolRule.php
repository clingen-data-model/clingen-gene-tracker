<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

interface ValidGeneSymbolRule extends Rule
{
    public function passes($attribute, $value);
    public function message();
}
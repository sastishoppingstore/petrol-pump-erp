<?php

namespace App\Rules;

use App\Support\PakistaniIbanValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PakistaniIban implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        if (! PakistaniIbanValidator::isValid((string) $value)) {
            $fail('The :attribute must be a valid 24-character Pakistani IBAN (PK + 2 digits + 4 letters + 16 alphanumeric characters) with valid MOD-97 checksum.');
        }
    }
}

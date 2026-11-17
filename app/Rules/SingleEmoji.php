<?php

namespace App\Rules;

use App\Support\Avatar;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SingleEmoji implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Avatar::isEmoji($value)) {
            $fail(__('users.avatar.invalid_emoji'));
        }
    }
}

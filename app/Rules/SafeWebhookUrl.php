<?php

namespace App\Rules;

use App\Support\WebhookUrlValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeWebhookUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! app(WebhookUrlValidator::class)->isAllowed($value)) {
            $fail('The :attribute must be a public HTTPS URL that is reachable from the internet.');
        }
    }
}

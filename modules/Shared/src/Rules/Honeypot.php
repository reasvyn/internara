<?php

declare(strict_types=1);

namespace Modules\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class Honeypot
 *
 * Anti-bot validation rule. Fails if the field contains any value.
 */
class Honeypot implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! empty($value)) {
            // No specific translation key to avoid giving bots clues. 
            // Just fail the request.
            $fail('Spam detected.');
        }
    }
}

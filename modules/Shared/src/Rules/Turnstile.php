<?php

declare(strict_types=1);

namespace Modules\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * Class Turnstile
 *
 * Validates Cloudflare Turnstile response tokens.
 */
class Turnstile implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.cloudflare.turnstile.secret_key');

        if (empty($secretKey)) {
            return;
        }

        $response = Http::asForm()->post(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            [
                'secret' => $secretKey,
                'response' => $value,
                'remoteip' => request()->ip(),
            ],
        );

        if (! $response->json('success')) {
            $fail(__('shared::validation.turnstile_failed'));
        }
    }
}

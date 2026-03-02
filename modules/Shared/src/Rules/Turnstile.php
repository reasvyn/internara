<?php

declare(strict_types=1);

namespace Modules\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Support\Facades\Http;

/**
 * Class Turnstile
 *
 * Validates Cloudflare Turnstile response tokens.
 * This is an Implicit Rule: it runs even if the value is empty,
 * allowing it to handle "optional requirement" based on configuration.
 */
class Turnstile implements ImplicitRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $siteKey = config('services.cloudflare.turnstile.site_key');
        $secretKey = config('services.cloudflare.turnstile.secret_key');

        // If Turnstile is not configured, it's always valid (even if empty)
        if (empty($siteKey) || empty($secretKey)) {
            return;
        }

        // If configured, the token becomes mandatory
        if (empty($value)) {
            $fail(__('validation.required', ['attribute' => $attribute]));

            return;
        }

        // Verify token with Cloudflare
        try {
            $response = Http::asForm()->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'secret' => $secretKey,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ],
            );

            if (! $response->json('success')) {
                $fail(__('shared::exceptions.turnstile_failed'));
            }
        } catch (\Exception $e) {
            $fail(__('shared::exceptions.turnstile_failed'));
        }
    }

    /**
     * Compatibility for older Laravel versions if needed.
     */
    public function passes($attribute, $value): bool
    {
        return true; // Logic is handled in validate()
    }

    public function message(): string
    {
        return (string) __('shared::exceptions.turnstile_failed');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Renders AuthException as a flash message for Livewire components.
 *
 * Provides a consistent way to display auth errors without exposing
 * sensitive technical details to the user.
 */
class AuthExceptionRenderer
{
    /**
     * Handle an AuthException within a Livewire component context.
     *
     * Logs the full error for developers and shows a user-friendly
     * flash message with an optional hint.
     */
    public static function handle(Component $component, AuthException $exception): void
    {
        Log::error(
            'Auth error: '.$exception->getMessage(),
            [
                'hint' => $exception->getHint(),
                'context' => $exception->getContext(),
                'trace' => $exception->getTraceAsString(),
            ],
        );

        $message = $exception->getMessage();

        if ($exception->getHint() !== null) {
            $message .= ' '.$exception->getHint();
        }

        flash()->error($message);

        if (app()->runningUnitTests()) {
            throw $exception;
        }
    }
}

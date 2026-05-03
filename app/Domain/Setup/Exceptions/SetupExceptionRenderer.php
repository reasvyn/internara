<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Renders SetupException as a flash message for Livewire components.
 *
 * This class provides a consistent way to display setup errors in the
 * web wizard without exposing sensitive technical details to the user.
 */
class SetupExceptionRenderer
{
    /**
     * Handle a SetupException within a Livewire component context.
     *
     * Logs the full error for developers and shows a user-friendly
     * flash message with an optional hint.
     */
    public static function handle(Component $component, SetupException $exception): void
    {
        Log::error(
            'Setup wizard error: '.$exception->getMessage(),
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

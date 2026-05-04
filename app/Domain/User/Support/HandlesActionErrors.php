<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Provides consistent error handling for Action classes.
 *
 * S2 - Sustain: Reduces code duplication and ensures uniform error logging.
 */
trait HandlesActionErrors
{
    /**
     * Wrap a closure with standardized error handling.
     *
     * @param \Closure(): mixed $callback
     * @param string $context Description of the operation for logging
     *
     * @throws RuntimeException
     */
    protected function withErrorHandling(callable $callback, string $context): mixed
    {
        try {
            return $callback();
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error($context, [
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(rtrim($context, '.').'.', 0, $e);
        }
    }
}

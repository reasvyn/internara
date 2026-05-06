<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Provides consistent error handling and logging for Action classes.
 *
 * Wraps callback execution with standardized try-catch blocks that
 * log errors and rethrow as RuntimeException, eliminating duplicated
 * error handling code across multiple Action classes.
 *
 * S2 - Sustain: Reduces code duplication and ensures uniform error logging.
 */
trait HandlesActionErrors
{
    /**
     * Wrap a callback with standardized error handling.
     *
     * Catches non-RuntimeException throwables, logs them with the provided
     * context message, and rethrows as RuntimeException.
     *
     * @param callable $callback The operation to execute
     * @param string $context Description of the operation for logging purposes
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

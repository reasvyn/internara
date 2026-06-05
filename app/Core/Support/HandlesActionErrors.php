<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Exceptions\AppException;
use App\Core\Exceptions\DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides consistent error handling and logging for Action classes.
 *
 * Wraps callback execution with standardized try-catch blocks that
 * log errors and rethrow as RuntimeException, eliminating duplicated
 * error handling code across multiple Action classes.
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
        } catch (RuntimeException|AppException|DomainException|ValidationException|AuthorizationException|ModelNotFoundException|NotFoundHttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            SmartLogger::error($context)
                ->withPayload([
                    'error' => $e->getMessage(),
                    'original_file' => $e->getFile(),
                    'original_line' => $e->getLine(),
                ])
                ->systemOnly()
                ->save();

            throw new RuntimeException(rtrim($context, '.').'.', 0, $e);
        }
    }
}

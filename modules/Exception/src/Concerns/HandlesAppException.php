<?php

declare(strict_types=1);

namespace Modules\Exception\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Exception\AppException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Trait HandlesAppException
 *
 * Provides a standardized way to interact with and handle AppException instances.
 */
trait HandlesAppException
{
    /**
     * Determines if the given Throwable is an AppException.
     */
    protected function isAppException(Throwable $exception): bool
    {
        return $exception instanceof AppException;
    }

    /**
     * Create a new AppException instance.
     */
    protected function newAppException(
        string $userMessage,
        array $replace = [],
        ?string $locale = null,
        ?string $logMessage = null,
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?Throwable $previous = null,
        array $context = [],
    ): AppException {
        return new AppException(
            userMessage: $userMessage,
            replace: $replace,
            locale: $locale,
            logMessage: $logMessage,
            code: $code,
            previous: $previous,
            context: $context,
        );
    }

    /**
     * Throw a new AppException instance.
     *
     * @throws AppException
     */
    protected function throwAppException(
        string $userMessage,
        array $replace = [],
        ?string $locale = null,
        ?string $logMessage = null,
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?Throwable $previous = null,
        array $context = [],
    ): void {
        throw $this->newAppException(
            userMessage: $userMessage,
            replace: $replace,
            locale: $locale,
            logMessage: $logMessage,
            code: $code,
            previous: $previous,
            context: $context,
        );
    }

    /**
     * Report an exception.
     */
    protected function reportException(Throwable $exception): void
    {
        report($exception);
    }

    /**
     * Render an AppException into an HTTP response.
     */
    protected function renderAppException(
        AppException $exception,
        Request $request,
    ): JsonResponse|RedirectResponse {
        return $exception->render($request);
    }

    /**
     * Handle an exception for Livewire components.
     */
    protected function handleAppExceptionInLivewire(Throwable $exception): void
    {
        $this->reportException($exception);

        $message = __('exception::messages.unexpected_error');

        if ($this->isAppException($exception)) {
            $message = $exception->getUserMessage();
        }

        if ($exception instanceof \Modules\Exception\RecordNotFoundException) {
            $message = $exception->getMessage();
            flash()->warning($message);

            return;
        }

        flash()->error($message);
    }

    /**
     * Handle an exception comprehensively based on the request type.
     */
    protected function handleAppException(
        Throwable $exception,
        Request $request,
    ): JsonResponse|RedirectResponse|array|null {
        $this->reportException($exception);

        if ($request->isLivewire()) {
            return $this->handleAppExceptionInLivewire($exception);
        }

        if ($this->isAppException($exception)) {
            return $this->renderAppException($exception, $request);
        }

        if ($request->expectsJson()) {
            return response()->json(
                [
                    'message' => is_debug_mode()
                        ? $exception->getMessage()
                        : __('exception::messages.unexpected_error'),
                ],
                method_exists($exception, 'getStatusCode')
                    ? $exception->getStatusCode()
                    : Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return redirect()
            ->back()
            ->withInput($request->input())
            ->with(
                'error',
                is_debug_mode()
                    ? $exception->getMessage()
                    : __('exception::messages.unexpected_error'),
            );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Exception\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Exception\AppException;
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
        int $code = 422,
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
        int $code = 422,
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
     *
     * @return array{event: string, message: string}
     */
    protected function handleAppExceptionInLivewire(Throwable $exception): array
    {
        $this->reportException($exception);

        $message = __('An unexpected error occurred.');
        $type = 'error';

        if ($this->isAppException($exception)) {
            $message = $exception->getUserMessage();
        }

        // If the component uses MaryUI Toast or has the trait, we dispatch the event.
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('toast', type: $type, title: $type === 'error' ? __('Error') : __('Success'), description: $message, icon: 'tabler.alert-circle');
        }

        return ['event' => $type, 'message' => $message];
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
                    'message' => config('app.debug')
                        ? $exception->getMessage()
                        : __('An unexpected error occurred.'),
                ],
                method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500,
            );
        }

        return redirect()
            ->back()
            ->withInput($request->input())
            ->with(
                'error',
                config('app.debug')
                    ? $exception->getMessage()
                    : __('An unexpected error occurred.'),
            );
    }
}

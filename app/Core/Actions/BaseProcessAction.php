<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Exceptions\RejectedException;
use App\Core\Services\SmartLogger;
use Illuminate\Contracts\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

abstract class BaseProcessAction extends BaseAction
{
    private array $progress = [];

    private array $results = [];

    protected function step(string $name, callable $callback): mixed
    {
        try {
            $result = $callback();

            $this->results[$name] = ['success' => true];

            return $result;
        } catch (Throwable $e) {
            $this->results[$name] = [
                'success' => false,
                'error' => $e->getMessage(),
            ];

            throw $e;
        }
    }

    protected function trackProgress(float $percent, ?string $message = null): void
    {
        $this->progress = [
            'percent' => min(100, max(0, $percent)),
            'message' => $message,
        ];
    }

    protected function getProgress(): array
    {
        return $this->progress;
    }

    protected function getResults(): array
    {
        return $this->results;
    }

    protected function allStepsSucceeded(): bool
    {
        foreach ($this->results as $result) {
            if (! ($result['success'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    protected function fail(string $message, array $context = []): never
    {
        throw new RejectedException($message, context: $context);
    }

    protected function notify(mixed $notifiables, Notification $notification): void
    {
        NotificationFacade::send($notifiables, $notification);
    }

    protected function logProgress(string $action, array $context = []): void
    {
        SmartLogger::info($action)
            ->module($this->moduleName())
            ->withPayload(array_merge($context, [
                'progress' => $this->progress,
                'steps' => $this->results,
            ]))
            ->withPiiMasking()
            ->systemOnly()
            ->save();
    }
}

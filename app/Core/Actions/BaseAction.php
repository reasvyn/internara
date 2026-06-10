<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Events\BaseEvent;
use App\Core\Support\HandlesActionErrors;
use App\Core\Support\SmartLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseAction
{
    use HandlesActionErrors;

    private array $pendingEvents = [];

    protected function transaction(callable $callback, int $attempts = 3): mixed
    {
        $this->beforeExecute();

        if (DB::transactionLevel() > 0) {
            $result = $callback();
            $this->dispatchPendingEvents();
            $this->afterExecute($result);

            return $result;
        }

        $result = DB::transaction(function () use ($callback) {
            $result = $callback();
            $this->dispatchPendingEvents();

            return $result;
        }, $attempts);

        $this->afterExecute($result);

        return $result;
    }

    protected function beforeExecute(): void {}

    protected function afterExecute(mixed $result): void {}

    protected function dispatchEvent(BaseEvent $event): void
    {
        $this->pendingEvents[] = $event;
    }

    private function dispatchPendingEvents(): void
    {
        foreach ($this->pendingEvents as $event) {
            event($event);
        }

        $this->pendingEvents = [];
    }

    protected function log(string $action, ?Model $subject = null, array $payload = []): void
    {
        SmartLogger::info($action)
            ->event($action)
            ->module($this->moduleName())
            ->about($subject)
            ->withPayload($payload)
            ->withPiiMasking()
            ->both()
            ->save();
    }

    protected function moduleName(): string
    {
        $namespaceParts = explode('\\', static::class);

        return $namespaceParts[1] ?? 'Unknown';
    }
}

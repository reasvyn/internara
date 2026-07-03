<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Actions\Concerns\HandlesActionErrors;
use App\Core\Events\BaseEvent;
use App\Core\Exceptions\RejectedException;
use App\Core\Services\SmartLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseAction
{
    use HandlesActionErrors;

    /** @var list<BaseEvent> */
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

    /**
     * Queue an event for dispatch after the current transaction commits.
     *
     * This is the canonical way to dispatch events from Actions. Events are
     * queued in $pendingEvents and flushed after DB::transaction() completes.
     * Do NOT call $event::dispatch() directly inside an Action — it would
     * fire immediately before the transaction commits.
     *
     * Events are for async communication only. If no listener exists in
     * config/event.php, do NOT create an event — $this->log() is sufficient.
     */
    protected function dispatchEvent(BaseEvent $event): void
    {
        $this->pendingEvents[] = $event;
    }

    /**
     * Throw a RejectedException for business rule violations.
     *
     * This is the only way to signal business rule rejection from Actions.
     * Do NOT throw RuntimeException — always use fail() or RejectedException.
     *
     * @param array<string, mixed> $context
     */
    protected function fail(string $message, array $context = []): never
    {
        $e = new RejectedException($message);

        if ($context !== []) {
            $e->withContext($context);
        }

        throw $e;
    }

    private function dispatchPendingEvents(): void
    {
        foreach ($this->pendingEvents as $event) {
            event($event);
        }

        $this->pendingEvents = [];
    }

    /**
     * @param array<string, mixed> $payload
     */
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
        $parts = explode('\\', static::class);

        if (count($parts) >= 2 && $parts[0] === 'App') {
            return $parts[1];
        }

        return 'Unknown';
    }
}

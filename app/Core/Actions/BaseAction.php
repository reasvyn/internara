<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Support\HandlesActionErrors;
use App\Core\Support\SmartLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseAction
{
    use HandlesActionErrors;

    protected function transaction(callable $callback, int $attempts = 3): mixed
    {
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        return DB::transaction($callback, $attempts);
    }

    protected function log(string $action, ?Model $subject = null, ?array $payload = null): void
    {
        SmartLogger::info($action)
            ->event($action)
            ->module($this->moduleName())
            ->about($subject)
            ->withPayload($payload ?? [])
            ->withPiiMasking()
            ->both()
            ->save();
    }

    protected function moduleName(): string
    {
        // Namespace structure: App\{Module}\...
        // parts[0] = 'App', parts[1] = '{Module}'
        $parts = explode('\\', static::class);

        return $parts[1] ?? 'Unknown';
    }
}

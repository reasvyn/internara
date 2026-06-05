<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Support\HandlesActionErrors;
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
        $parts = explode('\\', static::class);

        $domainIndex = array_search('Domain', $parts, true);

        if ($domainIndex !== false && isset($parts[$domainIndex + 1])) {
            return $parts[$domainIndex + 1];
        }

        return 'Unknown';
    }
}

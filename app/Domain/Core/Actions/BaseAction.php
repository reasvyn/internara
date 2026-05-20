<?php

declare(strict_types=1);

namespace App\Domain\Core\Actions;

use App\Domain\Core\Support\HandlesActionErrors;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseAction
{
    use HandlesActionErrors;

    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    protected function log(string $action, ?Model $subject = null, ?array $payload = null): void
    {
        SmartLogger::info($action)
            ->event($action)
            ->module($this->moduleName())
            ->about($subject)
            ->withPayload($payload ?? [])
            ->activityOnly()
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

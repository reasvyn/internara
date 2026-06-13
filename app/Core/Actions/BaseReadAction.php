<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Support\HandlesActionErrors;
use App\Core\Support\PiiMasker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

abstract class BaseReadAction
{
    use HandlesActionErrors;

    protected function remember(string $key, callable $callback, int $ttl = 300): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    protected function rememberForever(string $key, callable $callback): mixed
    {
        return Cache::rememberForever($key, $callback);
    }

    protected function forget(string $key): void
    {
        Cache::forget($key);
    }

    protected function cacheKey(string $purpose, string ...$qualifiers): string
    {
        $parts = [$this->moduleName(), $purpose];

        foreach ($qualifiers as $q) {
            $parts[] = $q;
        }

        return implode('.', $parts);
    }

    protected function mask(array $data, array $fields = []): array
    {
        if ($fields === []) {
            return PiiMasker::maskArray($data);
        }

        $result = $data;

        foreach ($fields as $field) {
            if (isset($result[$field])) {
                $result[$field] = PiiMasker::maskValue($field, $result[$field]);
            }
        }

        return $result;
    }

    protected function paginate(Builder $query, int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $query->paginate($perPage, $columns);
    }

    protected function format(mixed $data, ?int $total = null, int $perPage = 15): array
    {
        return [
            'data' => $data,
            'meta' => [
                'total' => $total ?? (is_countable($data) ? count($data) : null),
                'per_page' => $perPage,
            ],
        ];
    }

    private function moduleName(): string
    {
        $parts = explode('\\', static::class);

        if (count($parts) >= 2 && $parts[0] === 'App') {
            return $parts[1];
        }

        return 'Unknown';
    }
}

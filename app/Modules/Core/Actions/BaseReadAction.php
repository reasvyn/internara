<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Modules\Core\Actions\Concerns\HandlesActionErrors;
use App\Modules\Core\Actions\Concerns\ResolvesModuleName;
use App\Modules\Core\Support\PiiMasker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

abstract class BaseReadAction
{
    use HandlesActionErrors;
    use ResolvesModuleName;

    private const DEFAULT_CACHE_TTL = 300;
    private const DEFAULT_PER_PAGE = 15;

    protected function remember(string $key, callable $callback, int $ttl = self::DEFAULT_CACHE_TTL): mixed
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

    /**
     * @param array<string, mixed> $data
     * @param list<string> $fields
     *
     * @return array<string, mixed>
     */
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

    /**
     * @param list<string> $columns
     */
    protected function paginate(Builder $query, int $perPage = self::DEFAULT_PER_PAGE, array $columns = ['*']): LengthAwarePaginator
    {
        return $query->paginate($perPage, $columns);
    }

    /**
     * @return array{data: mixed, meta: array{total: int|null, per_page: int}}
     */
    protected function format(mixed $data, ?int $total = null, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        return [
            'data' => $data,
            'meta' => [
                'total' => $total ?? (is_countable($data) ? count($data) : null),
                'per_page' => $perPage,
            ],
        ];
    }
}

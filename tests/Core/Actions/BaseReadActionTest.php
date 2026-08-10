<?php

declare(strict_types=1);

use App\Core\Actions\BaseReadAction;
use App\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

final class TestReadAction extends BaseReadAction
{
    public function fetchReport(): array
    {
        return [];
    }

    public function doRemember(string $key, callable $callback, int $ttl = 300): mixed
    {
        return $this->remember($key, $callback, $ttl);
    }

    public function doRememberForever(string $key, callable $callback): mixed
    {
        return $this->rememberForever($key, $callback);
    }

    public function doForget(string $key): void
    {
        $this->forget($key);
    }

    public function doCacheKey(string $purpose, string ...$qualifiers): string
    {
        return $this->cacheKey($purpose, ...$qualifiers);
    }

    public function doMask(array $data, array $fields = []): array
    {
        return $this->mask($data, $fields);
    }

    public function doPaginate(Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginate($query, $perPage);
    }

    public function doFormat(mixed $data, ?int $total = null, int $perPage = 15): array
    {
        return $this->format($data, $total, $perPage);
    }
}

it('FR-A3: remember() caches values with a TTL', function () {
    $action = new TestReadAction;

    expect($action->doRemember('core.key', fn () => 42, 60))->toBe(42);
    expect(Cache::has('core.key'))->toBeTrue();
});

it('FR-A3: rememberForever() caches values indefinitely and forget() clears them', function () {
    $action = new TestReadAction;

    expect($action->doRememberForever('core.forever', fn () => 'value'))->toBe('value');
    expect(Cache::has('core.forever'))->toBeTrue();

    $action->doForget('core.forever');

    expect(Cache::has('core.forever'))->toBeFalse();
});

it('FR-A3: cacheKey() builds a module-prefixed dot-notation key', function () {
    expect((new TestReadAction)->doCacheKey('list', '1'))->toBe('Unknown.list.1');
    expect((new TestReadAction)->doCacheKey('list', '1', '2'))->toBe('Unknown.list.1.2');
});

it('FR-A3: mask() masks all sensitive keys or only the requested fields', function () {
    $action = new TestReadAction;

    expect($action->doMask(['password' => 'secret', 'count' => 1]))->toBe(['password' => '***', 'count' => 1]);
    expect($action->doMask(['email' => 'a@b.com', 'count' => 1], ['email']))->toBe(['email' => 'a***@b.com', 'count' => 1]);
});

it('FR-A3: paginate() returns a length-aware paginator', function () {
    User::factory()->count(3)->create();

    $paginator = (new TestReadAction)->doPaginate(User::query(), 2);

    expect($paginator->total())->toBe(3);
    expect($paginator->perPage())->toBe(2);
});

it('FR-A3: format() wraps data with pagination metadata', function () {
    $action = new TestReadAction;

    expect($action->doFormat([1, 2, 3]))->toBe([
        'data' => [1, 2, 3],
        'meta' => ['total' => 3, 'per_page' => 15],
    ]);
    expect($action->doFormat('item', 5, 10)['meta'])->toBe(['total' => 5, 'per_page' => 10]);
});

<?php

declare(strict_types=1);

use App\Core\Contracts\SettingsStore;

class MockSettingsStore implements SettingsStore
{
    /** @var array<string, mixed> */
    private array $store = [];

    public function __construct(array $defaults = [])
    {
        $this->store = $defaults;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }
}

test('settings store contract can be implemented', function () {
    $store = new MockSettingsStore;

    expect($store)->toBeInstanceOf(SettingsStore::class);
});

test('settings store returns value for existing key', function () {
    $store = new MockSettingsStore(['app.name' => 'Internara']);

    expect($store->get('app.name'))->toBe('Internara');
});

test('settings store returns default for missing key', function () {
    $store = new MockSettingsStore;

    expect($store->get('nonexistent'))->toBeNull();
    expect($store->get('nonexistent', 'fallback'))->toBe('fallback');
});

test('settings store handles mixed types', function () {
    $store = new MockSettingsStore([
        'debug' => true,
        'count' => 42,
        'rate' => 3.14,
        'tags' => ['a', 'b'],
    ]);

    expect($store->get('debug'))->toBeTrue();
    expect($store->get('count'))->toBe(42);
    expect($store->get('rate'))->toBe(3.14);
    expect($store->get('tags'))->toBe(['a', 'b']);
});

test('settings store returns null default for missing key implicitly', function () {
    $store = new MockSettingsStore;

    $result = $store->get('missing');

    expect($result)->toBeNull();
});

<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Support;

use App\Core\Support\LangChecker;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Facades\Log;
use Mockery;

test('lang checker logs warning on missing translation key', function () {
    $loader = Mockery::mock(Loader::class);
    $loader->shouldReceive('load')->andReturn([]);

    $log = Log::spy();

    $checker = new LangChecker($loader, 'en');
    $result = $checker->get('missing.key');

    expect($result)->toBe('missing.key');
    $log->shouldHaveReceived('warning')
        ->once()
        ->with('Missing translation key: missing.key', Mockery::type('array'));
});

test('lang checker returns translation when key exists', function () {
    $loader = Mockery::mock(Loader::class);
    $loader->shouldReceive('load')->andReturn(['existing' => 'Terjemahan']);

    $log = Log::spy();

    $checker = new LangChecker($loader, 'id');
    $result = $checker->get('existing');

    expect($result)->toBe('Terjemahan');
    $log->shouldNotHaveReceived('warning');
});

test('lang checker handles empty string key', function () {
    $loader = Mockery::mock(Loader::class);
    $loader->shouldReceive('load')->andReturn([]);

    $log = Log::spy();

    $checker = new LangChecker($loader, 'en');
    $result = $checker->get('');

    expect($result)->toBe('');
    $log->shouldHaveReceived('warning')
        ->once()
        ->with('Missing translation key: ', Mockery::type('array'));
});

test('lang checker uses specified locale', function () {
    $loader = Mockery::mock(Loader::class);
    $loader
        ->shouldReceive('load')
        ->with('id', '*', '*')
        ->andReturn(['greeting' => 'Halo']);

    $checker = new LangChecker($loader, 'id');
    $result = $checker->get('greeting', [], 'id');

    expect($result)->toBe('Halo');
});

test('lang checker falls back to default locale', function () {
    $loader = Mockery::mock(Loader::class);
    $loader->shouldReceive('load')->andReturn(['fallback' => 'Fallback value']);

    $checker = new LangChecker($loader, 'en');
    $result = $checker->get('fallback', [], 'es');

    expect($result)->toBe('Fallback value');
});

test('lang checker replaces placeholders', function () {
    $loader = Mockery::mock(Loader::class);
    $loader->shouldReceive('load')->andReturn(['welcome' => 'Hello :name']);

    $checker = new LangChecker($loader, 'en');
    $result = $checker->get('welcome', ['name' => 'John']);

    expect($result)->toBe('Hello John');
});

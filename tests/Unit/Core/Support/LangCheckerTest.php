<?php

declare(strict_types=1);

use App\Core\Support\LangChecker;
use Illuminate\Support\Facades\Log;
use Illuminate\Translation\ArrayLoader;

beforeEach(function () {
    $this->loader = new ArrayLoader;
    $this->loader->addMessages('en', 'test', [
        'hello' => 'Hello',
        'nested.greeting' => 'Hi there',
    ]);
});

test('LangChecker returns existing translations', function () {
    $checker = new LangChecker($this->loader, 'en');

    expect($checker->get('test.hello'))->toBe('Hello');
});

test('LangChecker returns nested translation', function () {
    $checker = new LangChecker($this->loader, 'en');

    expect($checker->get('test.nested.greeting'))->toBe('Hi there');
});

test('LangChecker logs warning for missing translation key', function () {
    $checker = new LangChecker($this->loader, 'en');

    Log::shouldReceive('warning')
        ->once()
        ->with(Mockery::on(fn ($msg) => str_contains($msg, 'Missing translation key: missing.key')), Mockery::any());

    $result = $checker->get('missing.key');
    expect($result)->toBe('missing.key');
});

test('LangChecker returns key unchanged when translation is missing', function () {
    $checker = new LangChecker($this->loader, 'en');

    expect($checker->get('nonexistent.key'))->toBe('nonexistent.key');
});

test('LangChecker does not log warning for existing key', function () {
    $checker = new LangChecker($this->loader, 'en');

    Log::shouldReceive('warning')->never();

    expect($checker->get('test.hello'))->toBe('Hello');
});

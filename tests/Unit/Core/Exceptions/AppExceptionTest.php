<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Exceptions;

use App\Core\Exceptions\AppException;

class MockAppException extends AppException {}

test('app exception holds hints and custom context', function () {
    $e = new MockAppException('Main message')
        ->withHint('Fix your settings')
        ->withContext(['key' => 'value']);

    expect($e->getMessage())->toBe('Main message');
    expect($e->getHint())->toBe('Fix your settings');
    expect($e->getContext())->toBe(['key' => 'value']);
});

test('app exception formats cli output correctly', function () {
    $e = new MockAppException('Main message')
        ->withHint('Run migration')
        ->withContext(['step' => 'migration']);

    $cli = $e->toCliOutput();

    expect($cli)->toContain('Main message');
    expect($cli)->toContain('Hint: Run migration');
    expect($cli)->toContain('step: migration');
});

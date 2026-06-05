<?php

declare(strict_types=1);

use App\Core\Exceptions\Concerns\HasExceptionContext;

test('HasExceptionContext provides hint via withHint and getHint', function () {
    $e = new class('Test error') extends RuntimeException
    {
        use HasExceptionContext;
    };

    $e->withHint('Try again later');
    expect($e->getHint())->toBe('Try again later');
});

test('HasExceptionContext provides context via withContext and getContext', function () {
    $e = new class('Test error') extends RuntimeException
    {
        use HasExceptionContext;
    };

    $e->withContext(['key' => 'value', 'count' => 5]);
    expect($e->getContext())->toBe([
        'key' => 'value',
        'count' => 5,
    ]);
});

test('HasExceptionContext toCliOutput formats with hint', function () {
    $e = new class('Something failed') extends RuntimeException
    {
        use HasExceptionContext;
    };

    $e->withHint('Check your input');
    $output = $e->toCliOutput();
    expect($output)->toContain('Something failed');
    expect($output)->toContain('Hint: Check your input');
});

test('HasExceptionContext toCliOutput formats with context', function () {
    $e = new class('Operation failed') extends RuntimeException
    {
        use HasExceptionContext;
    };

    $e->withContext(['user_id' => 42, 'role' => 'admin']);
    $output = $e->toCliOutput();
    expect($output)->toContain('Operation failed');
    expect($output)->toContain('user_id: 42');
    expect($output)->toContain('role: admin');
});

test('HasExceptionContext isUserFacing returns true by default', function () {
    $e = new class('Test') extends RuntimeException
    {
        use HasExceptionContext;
    };

    expect($e->isUserFacing())->toBeTrue();
});

test('HasExceptionContext shouldReport returns true by default', function () {
    $e = new class('Test') extends RuntimeException
    {
        use HasExceptionContext;
    };

    expect($e->shouldReport())->toBeTrue();
});

test('HasExceptionContext is chainable', function () {
    $e = new class('Test') extends RuntimeException
    {
        use HasExceptionContext;
    };

    $result = $e->withHint('hint')->withContext(['k' => 'v']);
    expect($result->getHint())->toBe('hint');
    expect($result->getContext())->toBe(['k' => 'v']);
});

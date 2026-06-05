<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Exceptions\Concerns;

use App\Core\Exceptions\Concerns\HasExceptionContext;
use RuntimeException;

class ContextTestException extends RuntimeException
{
    use HasExceptionContext;
}

test('with hint stores hint and returns self', function () {
    $e = new ContextTestException('test');
    $result = $e->withHint('Some hint');

    expect($result)->toBe($e);
    expect($e->getHint())->toBe('Some hint');
});

test('with context stores context and returns self', function () {
    $e = new ContextTestException('test');
    $result = $e->withContext(['key' => 'value']);

    expect($result)->toBe($e);
    expect($e->getContext())->toBe(['key' => 'value']);
});

test('get context defaults to empty array', function () {
    $e = new ContextTestException('test');

    expect($e->getContext())->toBe([]);
});

test('is user facing defaults to true', function () {
    $e = new ContextTestException('test');

    expect($e->isUserFacing())->toBeTrue();
});

test('should report defaults to true', function () {
    $e = new ContextTestException('test');

    expect($e->shouldReport())->toBeTrue();
});

test('to cli output includes message and hint', function () {
    $e = (new ContextTestException('Something broke'))
        ->withHint('Check your config');

    $output = $e->toCliOutput();

    expect($output)->toContain('Something broke');
    expect($output)->toContain('Hint: Check your config');
});

test('to cli output includes scalar context', function () {
    $e = (new ContextTestException('Error'))
        ->withContext(['user_id' => 42, 'role' => 'admin']);

    $output = $e->toCliOutput();

    expect($output)->toContain('user_id: 42');
    expect($output)->toContain('role: admin');
});

test('to cli output encodes non scalar context', function () {
    $e = (new ContextTestException('Error'))
        ->withContext(['items' => ['a', 'b', 'c']]);

    $output = $e->toCliOutput();

    expect($output)->toContain('items: '.json_encode(['a', 'b', 'c']));
});

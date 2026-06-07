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
    $e = new ContextTestException('Something broke')->withHint('Check your config');

    $output = $e->toCliOutput();

    expect($output)->toContain('Something broke');
    expect($output)->toContain('Hint: Check your config');
});

test('to cli output includes scalar context', function () {
    $e = new ContextTestException('Error')->withContext(['user_id' => 42, 'role' => 'admin']);

    $output = $e->toCliOutput();

    expect($output)->toContain('user_id: 42');
    expect($output)->toContain('role: admin');
});

test('to cli output encodes non scalar context', function () {
    $e = new ContextTestException('Error')->withContext(['items' => ['a', 'b', 'c']]);

    $output = $e->toCliOutput();

    expect($output)->toContain('items: '.json_encode(['a', 'b', 'c']));
});

test('to cli output sanitizes sensitive context', function () {
    $e = new ContextTestException('Error')->withContext([
        'email' => 'john@example.com',
        'password' => 'secret123',
        'user_id' => 42,
    ]);

    $output = $e->toCliOutput();

    expect($output)->toContain('email: jo***@example.com');
    expect($output)->toContain('password: ***');
    expect($output)->toContain('user_id: 42');
});

test('to cli output handles empty context', function () {
    $e = new ContextTestException('Just a message');

    $output = $e->toCliOutput();

    expect($output)->toBe('Just a message');
});

test('to cli output handles null hint', function () {
    $e = new ContextTestException('Test');
    $e->withHint(null);

    $output = $e->toCliOutput();

    expect($output)->toBe('Test');
});

test('to cli output handles special characters in context values', function () {
    $e = new ContextTestException('Error')->withContext(['path' => '/var/www/html']);

    $output = $e->toCliOutput();

    expect($output)->toContain('path: /var/www/html');
});

test('getSanitizedContext masks sensitive data', function () {
    $e = new ContextTestException('Error')->withContext([
        'email' => 'user@example.com',
        'token' => 'abc123',
        'name' => 'John Doe',
        'safe_key' => 'visible',
    ]);

    $sanitized = $e->getSanitizedContext();

    expect($sanitized['email'])->toBe('us***@example.com');
    expect($sanitized['token'])->toBe('***');
    expect($sanitized['name'])->toBe('J. Doe');
    expect($sanitized['safe_key'])->toBe('visible');
});

test('getSanitizedContext returns empty array for no context', function () {
    $e = new ContextTestException('Error');

    expect($e->getSanitizedContext())->toBe([]);
});

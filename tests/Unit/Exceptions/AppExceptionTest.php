<?php

declare(strict_types=1);

use App\Exceptions\ActionException;
use App\Exceptions\AppException;
use App\Exceptions\DomainException;
use App\Exceptions\InfrastructureException;
use App\Exceptions\PresentationException;

it('base exception can set hint', function () {
    $exception = new class('Test error') extends AppException {};
    $exception->withHint('Try again');

    expect($exception->getHint())->toBe('Try again');
});

it('base exception can set context', function () {
    $exception = new class('Test error') extends AppException {};
    $exception->withContext(['key' => 'value', 'count' => 5]);

    expect($exception->getContext())->toBe(['key' => 'value', 'count' => 5]);
});

it('base exception can render CLI output with hint', function () {
    $exception = new class('Test error') extends AppException {};
    $exception->withHint('Check logs');

    $output = $exception->toCliOutput();

    expect($output)->toContain('Test error');
    expect($output)->toContain('Hint: Check logs');
});

it('base exception can render CLI output with context', function () {
    $exception = new class('Test error') extends AppException {};
    $exception->withContext(['user_id' => 123, 'action' => 'create']);

    $output = $exception->toCliOutput();

    expect($output)->toContain('Test error');
    expect($output)->toContain('user_id: 123');
    expect($output)->toContain('action: create');
});

it('base exception CLI output without hint or context', function () {
    $exception = new class('Simple error') extends AppException {};

    $output = $exception->toCliOutput();

    expect($output)->toBe('Simple error');
});

it('base exception is user-facing by default', function () {
    $exception = new class('Test') extends AppException {};

    expect($exception->isUserFacing())->toBeTrue();
});

it('base exception should report by default', function () {
    $exception = new class('Test') extends AppException {};

    expect($exception->shouldReport())->toBeTrue();
});

it('action exception is user-facing', function () {
    $exception = new class('Action failed') extends ActionException {};

    expect($exception->isUserFacing())->toBeTrue();
});

it('infrastructure exception is not user-facing', function () {
    $exception = new class('DB fail') extends InfrastructureException {};

    expect($exception->isUserFacing())->toBeFalse();
});

it('presentation exception is user-facing', function () {
    $exception = new class('Invalid input') extends PresentationException {};

    expect($exception->isUserFacing())->toBeTrue();
});

it('can chain withHint and withContext', function () {
    $exception = new class('Test') extends AppException {};
    $exception->withHint('Hint here')
        ->withContext(['foo' => 'bar']);

    expect($exception->getHint())->toBe('Hint here');
    expect($exception->getContext())->toBe(['foo' => 'bar']);
});

it('action exception inherits report behavior', function () {
    $exception = new class('Test') extends ActionException {};

    expect($exception->shouldReport())->toBeTrue();
});

it('infrastructure exception inherits report behavior', function () {
    $exception = new class('Test') extends InfrastructureException {};

    expect($exception->shouldReport())->toBeTrue();
});

it('presentation exception inherits report behavior', function () {
    $exception = new class('Test') extends PresentationException {};

    expect($exception->shouldReport())->toBeTrue();
});

it('domain exception can set hint', function () {
    $exception = (new class('Domain error') extends DomainException {})
        ->withHint('Check configuration');

    expect($exception->getHint())->toBe('Check configuration');
});

it('domain exception can set context', function () {
    $exception = (new class('Domain error') extends DomainException {})
        ->withContext(['user_id' => 42]);

    expect($exception->getContext())->toBe(['user_id' => 42]);
});

it('domain exception can render CLI output with hint and context', function () {
    $exception = (new class('Domain error') extends DomainException {})
        ->withHint('Check configuration')
        ->withContext(['user_id' => 42]);

    $output = $exception->toCliOutput();

    expect($output)->toContain('Domain error');
    expect($output)->toContain('Hint: Check configuration');
    expect($output)->toContain('user_id: 42');
});

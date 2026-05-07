<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Support\Renderers\ExceptionRenderer;

it('can render exception to CLI output', function () {
    $exception = (new class('System audit check failed.') extends DomainException {})
        ->withHint('Review the audit output above and fix the failing checks before proceeding.')
        ->withContext(['step' => 'school']);

    $output = ExceptionRenderer::toCliOutput($exception);

    expect($output)->toContain('System audit check failed.');
    expect($output)->toContain('Hint:');
    expect($output)->toContain('step: school');
});

it('can render exception to Livewire flash', function () {
    $exception = (new class('Invalid setup token.') extends DomainException {})
        ->withHint('Use `php artisan setup:install` to generate a new token.');

    $result = ExceptionRenderer::toLivewireFlash($exception);

    expect($result)->toBeArray();
    expect($result['message'])->toBe('Invalid setup token.');
    expect($result['hint'])->toContain('php artisan setup:install');
    expect($result['type'])->toBe('error');
});

it('handles exception without hint for CLI', function () {
    $exception = new class('Application is already installed.') extends DomainException {};

    $output = ExceptionRenderer::toCliOutput($exception);

    expect($output)->toContain('Application is already installed.');
});

it('handles exception without hint for Livewire', function () {
    $exception = new class('Application is already installed.') extends DomainException {};

    $result = ExceptionRenderer::toLivewireFlash($exception);

    expect($result['message'])->toBe('Application is already installed.');
    expect($result['hint'])->toBeNull();
});

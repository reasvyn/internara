<?php

declare(strict_types=1);

use App\Exceptions\ActionException;
use App\Exceptions\AppException;
use App\Exceptions\DomainException;
use App\Exceptions\InfrastructureException;
use App\Exceptions\PresentationException;
use App\Support\Renderers\ExceptionRenderer;

describe('Exception Hierarchy', function () {
    it('action exception is user-facing', function () {
        $exception = new class('Action failed') extends ActionException {};

        expect($exception->isUserFacing())->toBeTrue();
    });

    it('infrastructure exception is not user-facing', function () {
        $exception = new class('DB connection failed') extends InfrastructureException {};

        expect($exception->isUserFacing())->toBeFalse();
    });

    it('presentation exception is user-facing', function () {
        $exception = new class('Invalid input') extends PresentationException {};

        expect($exception->isUserFacing())->toBeTrue();
    });

    it('base exception is user-facing by default', function () {
        $exception = new class('Generic error') extends AppException {};

        expect($exception->isUserFacing())->toBeTrue();
    });

    it('all exception types should report by default', function () {
        $action = new class('fail') extends ActionException {};
        $infra = new class('fail') extends InfrastructureException {};
        $present = new class('fail') extends PresentationException {};

        expect($action->shouldReport())->toBeTrue();
        expect($infra->shouldReport())->toBeTrue();
        expect($present->shouldReport())->toBeTrue();
    });
});

describe('Exception Context & Hints', function () {
    it('can chain hint and context fluently', function () {
        $exception = (new class('Chained error') extends AppException {})
            ->withHint('Check the logs')
            ->withContext(['trace_id' => 'abc-123', 'step' => 'validation']);

        expect($exception->getHint())->toBe('Check the logs');
        expect($exception->getContext())->toBe([
            'trace_id' => 'abc-123',
            'step' => 'validation',
        ]);
    });

    it('generates CLI output with hint and context', function () {
        $exception = (new class('Operation failed') extends AppException {})
            ->withHint('Retry the operation')
            ->withContext(['user_id' => 42]);

        $output = $exception->toCliOutput();

        expect($output)->toContain('Operation failed');
        expect($output)->toContain('Hint: Retry the operation');
        expect($output)->toContain('user_id: 42');
    });

    it('generates minimal CLI output without hint or context', function () {
        $exception = new class('Simple message') extends AppException {};

        expect($exception->toCliOutput())->toBe('Simple message');
    });
});

describe('ExceptionRenderer Integration', function () {
    it('renders DomainException to CLI output via renderer', function () {
        $exception = (new class('Audit check failed.') extends DomainException {})
            ->withHint('Review the audit output.')
            ->withContext(['step' => 'school']);

        $output = ExceptionRenderer::toCliOutput($exception);

        expect($output)->toContain('Audit check failed.');
        expect($output)->toContain('Hint:');
        expect($output)->toContain('step: school');
    });

    it('renders DomainException to Livewire flash array via renderer', function () {
        $exception = (new class('Invalid token.') extends DomainException {})
            ->withHint('Generate a new token.');

        $result = ExceptionRenderer::toLivewireFlash($exception);

        expect($result)->toBe([
            'message' => 'Invalid token.',
            'hint' => 'Generate a new token.',
            'type' => 'error',
        ]);
    });

    it('renders DomainException without hint to Livewire flash', function () {
        $exception = new class('Simple error.') extends DomainException {};

        $result = ExceptionRenderer::toLivewireFlash($exception);

        expect($result['message'])->toBe('Simple error.');
        expect($result['hint'])->toBeNull();
        expect($result['type'])->toBe('error');
    });

    it('renders DomainException without hint or context to CLI', function () {
        $exception = new class('Minimal error.') extends DomainException {};

        $output = ExceptionRenderer::toCliOutput($exception);

        expect($output)->toBe('Minimal error.');
    });
});

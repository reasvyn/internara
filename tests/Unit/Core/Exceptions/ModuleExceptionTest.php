<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Exceptions;

use App\Core\Exceptions\ModuleException;

class MockModuleException extends ModuleException {}

test('module exception holds hint and context and is user facing by default', function () {
    $e = (new MockModuleException('Business rule broken'))
        ->withHint('Contact supervisor');

    expect($e->getMessage())->toBe('Business rule broken');
    expect($e->getHint())->toBe('Contact supervisor');
    expect($e->isUserFacing())->toBeTrue();
});

test('module exception stores context', function () {
    $e = (new MockModuleException('Error'))
        ->withContext(['step' => 'validation']);

    expect($e->getContext())->toBe(['step' => 'validation']);
});

test('module exception should report by default', function () {
    $e = new MockModuleException('Error');

    expect($e->shouldReport())->toBeTrue();
});

test('module exception outputs cli format', function () {
    $e = (new MockModuleException('Module failed'))
        ->withHint('Restart the service')
        ->withContext(['code' => 500]);

    $output = $e->toCliOutput();

    expect($output)->toContain('Module failed');
    expect($output)->toContain('Hint: Restart the service');
    expect($output)->toContain('code: 500');
});

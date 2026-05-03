<?php

declare(strict_types=1);

namespace Modules\Log\Tests\Unit\Logging;

use Modules\Log\Logging\PiiMaskingProcessor;
use Monolog\Level;
use Monolog\LogRecord;

test('it masks sensitive fields in context', function () {
    $processor = new PiiMaskingProcessor;

    $record = new LogRecord(new \DateTimeImmutable, 'test', Level::Info, 'Log message', [
        'email' => 'user@example.com',
        'password' => 'secret123',
        'safe_field' => 'visible',
        'nested' => [
            'token' => 'top-secret-token',
        ],
    ]);

    $processed = $processor($record);
    $context = $processed->context;

    expect($context['email'])
        ->not->toBe('user@example.com')
        ->and($context['password'])
        ->toBe('*********')
        ->and($context['safe_field'])
        ->toBe('visible')
        ->and($context['nested']['token'])
        ->not->toBe('top-secret-token');
});

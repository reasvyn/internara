<?php

declare(strict_types=1);

use App\Auth\AccountRecovery\Data\RecoveryCodeData;

test('recovery code data can be created with all properties', function () {
    $data = new RecoveryCodeData(
        plainText: 'abc123',
        hashedToken: hash('sha256', 'abc123'),
        expiresAt: '2026-06-15 12:00:00',
    );

    expect($data->plainText)->toBe('abc123');
    expect($data->hashedToken)->toBe(hash('sha256', 'abc123'));
    expect($data->expiresAt)->toBe('2026-06-15 12:00:00');
});

test('recovery code data defaults expiresAt to null', function () {
    $data = new RecoveryCodeData(
        plainText: 'abc123',
        hashedToken: hash('sha256', 'abc123'),
    );

    expect($data->expiresAt)->toBeNull();
});

test('recovery code data is immutable', function () {
    $data = new RecoveryCodeData(plainText: 'u', hashedToken: 'h');

    $reflection = new ReflectionClass($data);
    foreach ($reflection->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});

test('recovery code data can be created from array', function () {
    $data = RecoveryCodeData::fromArray([
        'plainText' => 'from-array',
        'hashedToken' => hash('sha256', 'from-array'),
    ]);

    expect($data)->toBeInstanceOf(RecoveryCodeData::class);
    expect($data->plainText)->toBe('from-array');
});

test('recovery code data serializes to array', function () {
    $data = new RecoveryCodeData(
        plainText: 'serialize-test',
        hashedToken: hash('sha256', 'serialize-test'),
        expiresAt: '2026-07-01',
    );

    $array = $data->toArray();

    expect($array)->toHaveKeys(['plainText', 'hashedToken', 'expiresAt']);
    expect($array['plainText'])->toBe('serialize-test');
    expect($array['expiresAt'])->toBe('2026-07-01');
});

test('recovery code data from snake case keys works', function () {
    $data = RecoveryCodeData::fromArray([
        'plain_text' => 'snake-case',
        'hashed_token' => hash('sha256', 'snake-case'),
        'expires_at' => '2026-08-01',
    ]);

    expect($data)->toBeInstanceOf(RecoveryCodeData::class);
    expect($data->plainText)->toBe('snake-case');
    expect($data->expiresAt)->toBe('2026-08-01');
});

test('recovery code data throws when missing required params', function () {
    RecoveryCodeData::fromArray([]);
})->throws(InvalidArgumentException::class);

<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Domain\Core\Casts\SettingValueCast;
use App\Domain\Core\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use RuntimeException;

test('get decodes invalid json returns empty array and logs error', function () {
    $cast = new SettingValueCast;
    $model = Setting::make(['key' => 'bad.json', 'type' => 'json']);

    Log::shouldReceive('error')->once()->withArgs(function ($message, $context) {
        return str_contains($message, 'Invalid JSON in setting value')
            && isset($context['json_error']);
    });

    $result = $cast->get($model, 'value', '{invalid json}', ['type' => 'json']);

    expect($result)->toBe([]);
});

test('get handles array type same as json', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', '{"arr":[1,2,3]}', ['type' => 'array']);

    expect($result)->toBe(['arr' => [1, 2, 3]]);
});

test('get returns default type as string', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $result = $cast->get($model, 'value', 'some-value', []);

    expect($result)->toBe('some-value');
});

test('decrypt logs error on failure and returns original value', function () {
    $cast = new SettingValueCast;
    $model = Setting::make(['key' => 'bad.encrypted', 'type' => 'encrypted']);

    Log::shouldReceive('error')->once()->withArgs(function ($message, $context) {
        return str_contains($message, 'Failed to decrypt setting value')
            && isset($context['error']);
    });

    $result = $cast->get($model, 'value', 'not-encrypted-string', ['type' => 'encrypted']);

    expect($result)->toBe('not-encrypted-string');
});

test('set detects object type as json', function () {
    $cast = new SettingValueCast;
    $model = new Setting;

    $data = new \stdClass;
    $data->foo = 'bar';

    $result = $cast->set($model, 'value', $data, []);

    expect($result['type'])->toBe('json')
        ->and($result['value'])->toBe('{"foo":"bar"}');
});

test('encrypt throws exception on failure', function () {
    $cast = new SettingValueCast;
    $model = Setting::make(['key' => 'fail.encrypt', 'type' => 'encrypted']);

    Crypt::shouldReceive('encryptString')->andThrow(new \Exception('Encryption failed'));
    Log::shouldReceive('error')->once();

    $cast->set($model, 'value', 'secret-value', ['type' => 'encrypted']);
})->throws(RuntimeException::class, 'Failed to encrypt setting value.');

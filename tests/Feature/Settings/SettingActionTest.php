<?php

declare(strict_types=1);

use App\Modules\Settings\Actions\SetSettingAction;
use App\Modules\Settings\Data\SettingData;
use App\Modules\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

describe('YB22J: settings update action', function (): void {
    test('YB22J-FR-SET-003: set stores a new string setting and returns the model', function (): void {
        $result = app(SetSettingAction::class)->execute(new SettingData(
            key: 'tests.sample_string',
            value: 'hello internara',
            group: 'tests',
        ));

        expect($result)->toBeInstanceOf(Setting::class)
            ->and($result->key)->toBe('tests.sample_string')
            ->and($result->type)->toBe('string')
            ->and($result->value)->toBe('hello internara');

        $this->assertDatabaseHas('settings', ['key' => 'tests.sample_string']);
    });

    test('YB22J-FR-SET-003: set auto-detects the integer storage type', function (): void {
        $result = app(SetSettingAction::class)->execute(new SettingData(
            key: 'tests.sample_integer',
            value: 42,
            group: 'tests',
        ));

        expect($result->type)->toBe('integer')
            ->and($result->value)->toBe(42);
    });

    test('YB22J-FR-SET-003: set rewrites the value of an existing key', function (): void {
        $action = app(SetSettingAction::class);
        $action->execute(new SettingData(key: 'tests.rewrite_me', value: 'first', group: 'tests'));

        $result = $action->execute(new SettingData(key: 'tests.rewrite_me', value: 'second', group: 'tests'));

        expect($result->value)->toBe('second');
        expect(Setting::where('key', 'tests.rewrite_me')->count())->toBe(1);
    });

    test('YB22J-FR-SET-003: set rejects a key outside the allowed pattern', function (): void {
        expect(fn () => app(SetSettingAction::class)->execute(new SettingData(
            key: 'Bad Key With Spaces!',
            value: 'nope',
            group: 'tests',
        )))->toThrow(ValidationException::class);

        $this->assertDatabaseMissing('settings', ['key' => 'Bad Key With Spaces!']);
    });
});

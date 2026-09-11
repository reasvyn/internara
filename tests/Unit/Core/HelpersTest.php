<?php

declare(strict_types=1);

use App\Modules\User\Enums\AccountStatus;

describe('C8F0D: app_info helper', function (): void {
    test('C8F0D-FR-UTIL-002: app_info returns the full metadata map', function (): void {
        $all = app_info();

        expect($all)->toBeArray();
        expect($all)->toHaveKeys(['name', 'version']);
        expect($all['name'])->toBeString();
    });

    test('C8F0D-FR-UTIL-002: app_info resolves a single key with default fallback', function (): void {
        expect(app_info('name'))->toBe(app_info()['name']);
        expect(app_info('version'))->toBe(app_info()['version']);
        expect(app_info('missing.key deep', 'fallback'))->toBe('fallback');
        expect(app_info('missing.key deep'))->toBeNull();
    });
});

describe('ts_options helper', function (): void {
    test('it prepends the placeholder with an empty value', function (): void {
        $options = ts_options([], 'Pilih salah satu');

        expect($options)->toBe([['label' => 'Pilih salah satu', 'value' => '']]);
    });

    test('it normalizes a value-to-label map preserving string keys', function (): void {
        $options = ts_options(['teacher' => 'Guru', 'student' => 'Siswa']);

        expect($options)->toBe([
            ['label' => 'Guru', 'value' => 'teacher'],
            ['label' => 'Siswa', 'value' => 'student'],
        ]);
    });

    test('it maps backed enums through their label and value', function (): void {
        $options = ts_options([AccountStatus::VERIFIED, AccountStatus::SUSPENDED]);

        expect($options)->toBe([
            ['label' => AccountStatus::VERIFIED->label(), 'value' => 'verified'],
            ['label' => AccountStatus::SUSPENDED->label(), 'value' => 'suspended'],
        ]);
    });

    test('it reads label and value attributes from arrays and models', function (): void {
        $options = ts_options([['id' => '1', 'name' => 'RPL'], ['id' => '2', 'name' => 'TKJ']]);

        expect($options)->toBe([
            ['label' => 'RPL', 'value' => '1'],
            ['label' => 'TKJ', 'value' => '2'],
        ]);
    });

    test('it accepts collections and custom attribute keys', function (): void {
        $options = ts_options(
            collect([['code' => 'A', 'title' => 'Alpha']]),
            null,
            'title',
            'code',
        );

        expect($options)->toBe([['label' => 'Alpha', 'value' => 'A']]);
    });

    test('it rejects an explicit null item source with a TypeError', function (): void {
        expect(fn (): array => ts_options(null))->toThrow(TypeError::class);
    });
});

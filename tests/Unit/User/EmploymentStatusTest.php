<?php

declare(strict_types=1);

use App\Modules\User\Enums\EmploymentStatus;

describe('EmploymentStatus enum', function (): void {
    test('cases carry the specified backing values: cases carry the specified backing values', function (): void {
        expect(EmploymentStatus::FULL_TIME->value)->toBe('full_time');
        expect(EmploymentStatus::from('full_time'))->toBe(EmploymentStatus::FULL_TIME);
        expect(EmploymentStatus::PART_TIME->value)->toBe('part_time');
        expect(EmploymentStatus::from('part_time'))->toBe(EmploymentStatus::PART_TIME);
        expect(EmploymentStatus::CONTRACT->value)->toBe('contract');
        expect(EmploymentStatus::from('contract'))->toBe(EmploymentStatus::CONTRACT);
        expect(EmploymentStatus::TEMPORARY->value)->toBe('temporary');
        expect(EmploymentStatus::from('temporary'))->toBe(EmploymentStatus::TEMPORARY);
        expect(EmploymentStatus::VOLUNTEER->value)->toBe('volunteer');
        expect(EmploymentStatus::from('volunteer'))->toBe(EmploymentStatus::VOLUNTEER);
        expect(EmploymentStatus::cases())->toHaveCount(5);
        expect(EmploymentStatus::tryFrom('no-such-value'))->toBeNull();
    });
    test('labels resolve in both locales: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(EmploymentStatus::FULL_TIME->label())->toBe('Full-time');
        expect(EmploymentStatus::PART_TIME->label())->toBe('Part-time');
        expect(EmploymentStatus::CONTRACT->label())->toBe('Contract');
        expect(EmploymentStatus::TEMPORARY->label())->toBe('Temporary');
        expect(EmploymentStatus::VOLUNTEER->label())->toBe('Volunteer');
    });

    test('labels resolve in both locales: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(EmploymentStatus::FULL_TIME->label())->toBe('Tetap');
        expect(EmploymentStatus::PART_TIME->label())->toBe('Paruh Waktu');
        expect(EmploymentStatus::CONTRACT->label())->toBe('Kontrak');
        expect(EmploymentStatus::TEMPORARY->label())->toBe('Sementara');
        expect(EmploymentStatus::VOLUNTEER->label())->toBe('Relawan');
    });
    test('options() exposes every case as an id/name pair', function (): void {
        app()->setLocale('en');
        $options = EmploymentStatus::options();

        expect($options)->toHaveCount(5);
        expect($options[0])->toBe(['id' => 'full_time', 'name' => 'Full-time']);
        expect($options[4])->toBe(['id' => 'volunteer', 'name' => 'Volunteer']);
    });
});

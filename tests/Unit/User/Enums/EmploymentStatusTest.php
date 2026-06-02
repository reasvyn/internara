<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\User\Enums\EmploymentStatus;

describe('EmploymentStatus', function () {
    it('has five cases', function () {
        expect(EmploymentStatus::cases())->toHaveCount(5);
    });

    it('has correct values', function () {
        expect(EmploymentStatus::FULL_TIME->value)->toBe('full_time');
        expect(EmploymentStatus::PART_TIME->value)->toBe('part_time');
        expect(EmploymentStatus::CONTRACT->value)->toBe('contract');
        expect(EmploymentStatus::TEMPORARY->value)->toBe('temporary');
        expect(EmploymentStatus::VOLUNTEER->value)->toBe('volunteer');
    });

    it('returns label for each case', function () {
        foreach (EmploymentStatus::cases() as $case) {
            expect($case->label())->toBeString();
        }
    });

    it('provides options array', function () {
        $options = EmploymentStatus::options();

        expect($options)->toBeArray();
        expect($options)->toHaveCount(5);

        foreach ($options as $option) {
            expect($option)->toHaveKeys(['id', 'name']);
        }
    });

    it('implements LabelEnum', function () {
        expect(EmploymentStatus::FULL_TIME)->toBeInstanceOf(LabelEnum::class);
    });
});

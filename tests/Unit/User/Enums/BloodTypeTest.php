<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\User\Enums\BloodType;

describe('BloodType', function () {
    it('has four cases', function () {
        expect(BloodType::cases())->toHaveCount(4);
    });

    it('has correct values', function () {
        expect(BloodType::A->value)->toBe('a');
        expect(BloodType::B->value)->toBe('b');
        expect(BloodType::AB->value)->toBe('ab');
        expect(BloodType::O->value)->toBe('o');
    });

    it('returns value as label', function () {
        foreach (BloodType::cases() as $case) {
            expect($case->label())->toBe($case->value);
        }
    });

    it('implements LabelEnum', function () {
        expect(BloodType::A)->toBeInstanceOf(LabelEnum::class);
    });
});

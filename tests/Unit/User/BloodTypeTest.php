<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\User\Enums\BloodType;

describe('BloodType enum', function () {
    it('is string-backed', function () {
        expect(BloodType::A->value)->toBe('a');
    });

    it('implements LabelEnum', function () {
        expect(BloodType::A)->toBeInstanceOf(LabelEnum::class);
    });

    it('has all types', function () {
        expect(BloodType::cases())->toHaveCount(4);
    });
});

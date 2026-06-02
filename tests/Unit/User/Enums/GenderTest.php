<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\User\Enums\Gender;

describe('Gender', function () {
    it('has male and female cases', function () {
        expect(Gender::cases())->toHaveCount(2);
        expect(Gender::MALE->value)->toBe('male');
        expect(Gender::FEMALE->value)->toBe('female');
    });

    it('returns label for male', function () {
        expect(Gender::MALE->label())->toBeString();
    });

    it('returns label for female', function () {
        expect(Gender::FEMALE->label())->toBeString();
    });

    it('implements LabelEnum', function () {
        expect(Gender::MALE)->toBeInstanceOf(LabelEnum::class);
    });
});

<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\User\Enums\Gender;

describe('Gender enum', function () {
    it('is string-backed', function () {
        expect(Gender::MALE->value)->toBe('male');
    });

    it('implements LabelEnum', function () {
        expect(Gender::MALE)->toBeInstanceOf(LabelEnum::class);
    });

    it('returns labels', function () {
        expect(Gender::MALE->label())->toBeString();
    });
});

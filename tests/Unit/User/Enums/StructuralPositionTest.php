<?php

declare(strict_types=1);

use App\Domain\Core\Contracts\LabelEnum;
use App\Domain\User\Enums\StructuralPosition;

describe('StructuralPosition', function () {
    it('has six cases', function () {
        expect(StructuralPosition::cases())->toHaveCount(6);
    });

    it('has correct values', function () {
        expect(StructuralPosition::PRINCIPAL->value)->toBe('principal');
        expect(StructuralPosition::VICE_PRINCIPAL->value)->toBe('vice_principal');
        expect(StructuralPosition::HEAD_OF_DEPARTMENT->value)->toBe('head_of_department');
        expect(StructuralPosition::PROGRAM_COORDINATOR->value)->toBe('program_coordinator');
        expect(StructuralPosition::SUPERVISING_TEACHER->value)->toBe('supervising_teacher');
        expect(StructuralPosition::INDUSTRY_SUPERVISOR->value)->toBe('industry_supervisor');
    });

    it('returns label for each case', function () {
        foreach (StructuralPosition::cases() as $case) {
            expect($case->label())->toBeString();
        }
    });

    it('implements LabelEnum', function () {
        expect(StructuralPosition::PRINCIPAL)->toBeInstanceOf(LabelEnum::class);
    });
});

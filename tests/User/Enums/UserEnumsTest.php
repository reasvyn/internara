<?php

declare(strict_types=1);

use App\User\Enums\BloodType;
use App\User\Enums\EmploymentStatus;
use App\User\Enums\Gender;
use App\User\Enums\StructuralPosition;

describe('BloodType', function () {
    it('has all expected cases', function () {
        expect(BloodType::cases())->toHaveCount(4);
        expect(BloodType::A->value)->toBe('a');
        expect(BloodType::B->value)->toBe('b');
        expect(BloodType::AB->value)->toBe('ab');
        expect(BloodType::O->value)->toBe('o');
    });

    it('has label for each case', function () {
        foreach (BloodType::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });
});

describe('Gender', function () {
    it('has all expected cases', function () {
        expect(Gender::cases())->toHaveCount(2);
        expect(Gender::MALE->value)->toBe('male');
        expect(Gender::FEMALE->value)->toBe('female');
    });

    it('has label for each case', function () {
        foreach (Gender::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });
});

describe('StructuralPosition', function () {
    it('has all expected cases', function () {
        expect(StructuralPosition::cases())->toHaveCount(6);
        expect(StructuralPosition::PRINCIPAL->value)->toBe('principal');
        expect(StructuralPosition::VICE_PRINCIPAL->value)->toBe('vice_principal');
        expect(StructuralPosition::HEAD_OF_DEPARTMENT->value)->toBe('head_of_department');
        expect(StructuralPosition::PROGRAM_COORDINATOR->value)->toBe('program_coordinator');
        expect(StructuralPosition::SUPERVISING_TEACHER->value)->toBe('supervising_teacher');
        expect(StructuralPosition::INDUSTRY_SUPERVISOR->value)->toBe('industry_supervisor');
    });

    it('has label for each case', function () {
        foreach (StructuralPosition::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });
});

describe('EmploymentStatus', function () {
    it('has all expected cases', function () {
        expect(EmploymentStatus::cases())->toHaveCount(5);
        expect(EmploymentStatus::FULL_TIME->value)->toBe('full_time');
        expect(EmploymentStatus::PART_TIME->value)->toBe('part_time');
        expect(EmploymentStatus::CONTRACT->value)->toBe('contract');
        expect(EmploymentStatus::TEMPORARY->value)->toBe('temporary');
        expect(EmploymentStatus::VOLUNTEER->value)->toBe('volunteer');
    });

    it('has label for each case', function () {
        foreach (EmploymentStatus::cases() as $case) {
            expect($case->label())->toBeString()->not->toBeEmpty();
        }
    });

    it('options returns array of id/name maps', function () {
        $options = EmploymentStatus::options();

        expect($options)->toBeArray();
        expect($options)->toHaveCount(5);
        expect($options[0])->toHaveKeys(['id', 'name']);
    });
});

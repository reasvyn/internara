<?php

declare(strict_types=1);

use App\User\Enums\BloodType;
use App\User\Enums\EmploymentStatus;
use App\User\Enums\Gender;
use App\User\Enums\StructuralPosition;

describe('BloodType', function () {
    it('has label for each case', function () {
        foreach (BloodType::cases() as $case) {
            expect($case->label())->toBeString();
        }
    });
});

describe('Gender', function () {
    it('has label for each case', function () {
        foreach (Gender::cases() as $case) {
            expect($case->label())->toBeString();
        }
    });
});

describe('StructuralPosition', function () {
    it('has label for each case', function () {
        foreach (StructuralPosition::cases() as $case) {
            expect($case->label())->toBeString();
        }
    });
});

describe('EmploymentStatus', function () {
    it('has label for each case', function () {
        foreach (EmploymentStatus::cases() as $case) {
            expect($case->label())->toBeString();
        }
    });
});
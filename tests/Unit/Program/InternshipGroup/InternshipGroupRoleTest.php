<?php

declare(strict_types=1);

use App\Core\Contracts\LabelEnum;
use App\Program\InternshipGroup\Enums\InternshipGroupRole;

describe('cases', function () {
    it('has student case', function () {
        expect(InternshipGroupRole::STUDENT->value)->toBe('student');
    });

    it('has school teacher case', function () {
        expect(InternshipGroupRole::SCHOOL_TEACHER->value)->toBe('school_teacher');
    });

    it('has industry supervisor case', function () {
        expect(InternshipGroupRole::INDUSTRY_SUPERVISOR->value)->toBe('industry_supervisor');
    });
});

describe('label', function () {
    it('returns label for each role', function () {
        foreach (InternshipGroupRole::cases() as $role) {
            expect($role->label())->toBeString();
        }
    });

    it('implements LabelEnum', function () {
        expect(InternshipGroupRole::STUDENT)->toBeInstanceOf(LabelEnum::class);
    });
});

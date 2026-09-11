<?php

declare(strict_types=1);

use App\Modules\Program\Domain\InternshipGroup\Enums\InternshipGroupRole;

describe('IT0OE: InternshipGroupRole enum', function (): void {
    test('IT0OE-FR-GROUP-015: cases carry the specified backing values', function (): void {
        expect(InternshipGroupRole::STUDENT->value)->toBe('student');
        expect(InternshipGroupRole::from('student'))->toBe(InternshipGroupRole::STUDENT);
        expect(InternshipGroupRole::SCHOOL_TEACHER->value)->toBe('school_teacher');
        expect(InternshipGroupRole::from('school_teacher'))->toBe(InternshipGroupRole::SCHOOL_TEACHER);
        expect(InternshipGroupRole::INDUSTRY_SUPERVISOR->value)->toBe('industry_supervisor');
        expect(InternshipGroupRole::from('industry_supervisor'))->toBe(InternshipGroupRole::INDUSTRY_SUPERVISOR);
        expect(InternshipGroupRole::cases())->toHaveCount(3);
        expect(InternshipGroupRole::tryFrom('no-such-value'))->toBeNull();
    });
    test('IT0OE-FR-GROUP-015: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(InternshipGroupRole::STUDENT->label())->toBe('Student');
        expect(InternshipGroupRole::SCHOOL_TEACHER->label())->toBe('School Teacher');
        expect(InternshipGroupRole::INDUSTRY_SUPERVISOR->label())->toBe('Industry Supervisor');
    });

    test('IT0OE-FR-GROUP-015: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(InternshipGroupRole::STUDENT->label())->toBe('Siswa');
        expect(InternshipGroupRole::SCHOOL_TEACHER->label())->toBe('Guru Pembimbing');
        expect(InternshipGroupRole::INDUSTRY_SUPERVISOR->label())->toBe('Pembimbing Industri');
    });
});

<?php

declare(strict_types=1);

use App\Modules\Document\Domain\Handbook\Enums\HandbookAudience;

describe('ZUFG8: HandbookAudience enum', function (): void {
    test('ZUFG8-FR-HAND-003: cases carry the specified backing values', function (): void {
        expect(HandbookAudience::ALL->value)->toBe('all');
        expect(HandbookAudience::from('all'))->toBe(HandbookAudience::ALL);
        expect(HandbookAudience::STUDENT->value)->toBe('student');
        expect(HandbookAudience::from('student'))->toBe(HandbookAudience::STUDENT);
        expect(HandbookAudience::TEACHER->value)->toBe('teacher');
        expect(HandbookAudience::from('teacher'))->toBe(HandbookAudience::TEACHER);
        expect(HandbookAudience::SUPERVISOR->value)->toBe('supervisor');
        expect(HandbookAudience::from('supervisor'))->toBe(HandbookAudience::SUPERVISOR);
        expect(HandbookAudience::cases())->toHaveCount(4);
        expect(HandbookAudience::tryFrom('no-such-value'))->toBeNull();
    });
    test('ZUFG8-FR-HAND-006: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(HandbookAudience::ALL->label())->toBe('All Roles');
        expect(HandbookAudience::STUDENT->label())->toBe('Students');
        expect(HandbookAudience::TEACHER->label())->toBe('Teachers');
        expect(HandbookAudience::SUPERVISOR->label())->toBe('Supervisors');
    });

    test('ZUFG8-FR-HAND-006: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(HandbookAudience::ALL->label())->toBe('Semua Peran');
        expect(HandbookAudience::STUDENT->label())->toBe('Siswa');
        expect(HandbookAudience::TEACHER->label())->toBe('Guru');
        expect(HandbookAudience::SUPERVISOR->label())->toBe('Supervisor');
    });
});

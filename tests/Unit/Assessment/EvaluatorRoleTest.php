<?php

declare(strict_types=1);

use App\Modules\Assessment\Enums\EvaluatorRole;

describe('ARDA6: EvaluatorRole enum', function (): void {
    test('ARDA6-FR-ASM-002: cases carry the specified backing values', function (): void {
        expect(EvaluatorRole::ADMIN->value)->toBe('admin');
        expect(EvaluatorRole::from('admin'))->toBe(EvaluatorRole::ADMIN);
        expect(EvaluatorRole::TEACHER->value)->toBe('teacher');
        expect(EvaluatorRole::from('teacher'))->toBe(EvaluatorRole::TEACHER);
        expect(EvaluatorRole::SUPERVISOR->value)->toBe('supervisor');
        expect(EvaluatorRole::from('supervisor'))->toBe(EvaluatorRole::SUPERVISOR);
        expect(EvaluatorRole::SYSTEM->value)->toBe('system');
        expect(EvaluatorRole::from('system'))->toBe(EvaluatorRole::SYSTEM);
        expect(EvaluatorRole::cases())->toHaveCount(4);
        expect(EvaluatorRole::tryFrom('no-such-value'))->toBeNull();
    });
    test('ARDA6-FR-ASM-008: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(EvaluatorRole::ADMIN->label())->toBe('Admin');
        expect(EvaluatorRole::TEACHER->label())->toBe('Teacher');
        expect(EvaluatorRole::SUPERVISOR->label())->toBe('Industry Supervisor');
        expect(EvaluatorRole::SYSTEM->label())->toBe('System (Auto)');
    });

    test('ARDA6-FR-ASM-008: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(EvaluatorRole::ADMIN->label())->toBe('Admin');
        expect(EvaluatorRole::TEACHER->label())->toBe('Guru');
        expect(EvaluatorRole::SUPERVISOR->label())->toBe('Pembimbing Industri');
        expect(EvaluatorRole::SYSTEM->label())->toBe('Sistem (Otomatis)');
    });
});

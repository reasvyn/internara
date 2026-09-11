<?php

declare(strict_types=1);

use App\Modules\User\Enums\StructuralPosition;

describe('StructuralPosition enum', function (): void {
    test('cases carry the specified backing values: cases carry the specified backing values', function (): void {
        expect(StructuralPosition::PRINCIPAL->value)->toBe('principal');
        expect(StructuralPosition::from('principal'))->toBe(StructuralPosition::PRINCIPAL);
        expect(StructuralPosition::VICE_PRINCIPAL->value)->toBe('vice_principal');
        expect(StructuralPosition::from('vice_principal'))->toBe(StructuralPosition::VICE_PRINCIPAL);
        expect(StructuralPosition::HEAD_OF_DEPARTMENT->value)->toBe('head_of_department');
        expect(StructuralPosition::from('head_of_department'))->toBe(StructuralPosition::HEAD_OF_DEPARTMENT);
        expect(StructuralPosition::PROGRAM_COORDINATOR->value)->toBe('program_coordinator');
        expect(StructuralPosition::from('program_coordinator'))->toBe(StructuralPosition::PROGRAM_COORDINATOR);
        expect(StructuralPosition::SUPERVISING_TEACHER->value)->toBe('supervising_teacher');
        expect(StructuralPosition::from('supervising_teacher'))->toBe(StructuralPosition::SUPERVISING_TEACHER);
        expect(StructuralPosition::INDUSTRY_SUPERVISOR->value)->toBe('industry_supervisor');
        expect(StructuralPosition::from('industry_supervisor'))->toBe(StructuralPosition::INDUSTRY_SUPERVISOR);
        expect(StructuralPosition::cases())->toHaveCount(6);
        expect(StructuralPosition::tryFrom('no-such-value'))->toBeNull();
    });
    test('labels resolve in both locales: labels resolve in English', function (): void {
        app()->setLocale('en');

        expect(StructuralPosition::PRINCIPAL->label())->toBe('Principal');
        expect(StructuralPosition::VICE_PRINCIPAL->label())->toBe('Vice Principal');
        expect(StructuralPosition::HEAD_OF_DEPARTMENT->label())->toBe('Head of Department');
        expect(StructuralPosition::PROGRAM_COORDINATOR->label())->toBe('Program Coordinator');
        expect(StructuralPosition::SUPERVISING_TEACHER->label())->toBe('Supervising Teacher');
        expect(StructuralPosition::INDUSTRY_SUPERVISOR->label())->toBe('Industry Supervisor');
    });

    test('labels resolve in both locales: labels resolve in Indonesian', function (): void {
        app()->setLocale('id');

        expect(StructuralPosition::PRINCIPAL->label())->toBe('Kepala Sekolah');
        expect(StructuralPosition::VICE_PRINCIPAL->label())->toBe('Wakil Kepala Sekolah');
        expect(StructuralPosition::HEAD_OF_DEPARTMENT->label())->toBe('Kepala Jurusan');
        expect(StructuralPosition::PROGRAM_COORDINATOR->label())->toBe('Koordinator Program');
        expect(StructuralPosition::SUPERVISING_TEACHER->label())->toBe('Guru Pembimbing');
        expect(StructuralPosition::INDUSTRY_SUPERVISOR->label())->toBe('Supervisor Industri');
    });
});

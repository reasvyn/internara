<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Setup\Domain\SetupWizard\Actions\SetupDepartmentAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

describe('VEJCX: SetupDepartmentAction', function (): void {
    test('VEJCX-FR-W9: name is required', function (): void {
        expect(fn () => app(SetupDepartmentAction::class)->execute(['name' => '']))
            ->toThrow(ValidationException::class);
    });

    test('VEJCX-FR-F3: creates the first department in the database', function (): void {
        $department = app(SetupDepartmentAction::class)->execute([
            'name' => 'Rekayasa Perangkat Lunak',
            'description' => 'Jurusan utama',
        ]);

        expect($department)->toBeInstanceOf(Department::class);
        $this->assertModelExists($department);
        expect($department->name)->toBe('Rekayasa Perangkat Lunak');
        expect($department->description)->toBe('Jurusan utama');
    });
});

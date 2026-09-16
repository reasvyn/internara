<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\AccountApplication\Livewire\Forms\AccountApplicationForm;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

function aaShapeForm(array $state): AccountApplicationForm
{
    $form = (new ReflectionClass(AccountApplicationForm::class))->newInstanceWithoutConstructor();

    foreach ($state as $key => $value) {
        $form->{$key} = $value;
    }

    return $form;
}

describe('920SO: application form shape', function (): void {
    test('920SO-FR-APPLY-027: placement mode flattens with the placement kept', function (): void {
        $form = aaShapeForm([
            'name' => 'Shape Kid',
            'email' => 'shape-place@example.com',
            'phone' => '0812',
            'address' => 'Jl. Shape',
            'national_id_number' => 'NID-9',
            'student_id_number' => 'NIS-9',
            'department_id' => 'dept-1',
            'class_name' => 'XII-RPL-1',
            'entry_year' => '2024',
            'internship_id' => 'intern-1',
            'placement_id' => 'place-1',
            'academic_year' => '2025/2026',
            'proposed_company_name' => 'PT Stale',
            'proposed_company_address' => 'Jl. Stale',
            'use_placement' => true,
        ]);

        $flat = $form->toArray();

        expect($flat)->toBe([
            'name' => 'Shape Kid',
            'email' => 'shape-place@example.com',
            'phone' => '0812',
            'address' => 'Jl. Shape',
            'national_id_number' => 'NID-9',
            'student_id_number' => 'NIS-9',
            'department_id' => 'dept-1',
            'class_name' => 'XII-RPL-1',
            'entry_year' => 2024,
            'internship_id' => 'intern-1',
            'placement_id' => 'place-1',
            'academic_year' => '2025/2026',
            'proposed_company_name' => null,
            'proposed_company_address' => null,
        ]);
    });

    test('920SO-FR-APPLY-026: proposal mode demands company evidence through the real validator', function (): void {
        $form = aaShapeForm(['use_placement' => false]);

        expect(array_keys($form->rules()))->toContain('proposed_company_name', 'proposed_company_address')
            ->and(array_keys($form->rules()))->not->toContain('placement_id');

        $empty = Validator::make(
            ['proposed_company_name' => null, 'proposed_company_address' => ''],
            Arr::only($form->rules(), ['proposed_company_name', 'proposed_company_address'])
        );

        expect($empty->fails())->toBeTrue()
            ->and($empty->errors()->has('proposed_company_name'))->toBeTrue()
            ->and($empty->errors()->has('proposed_company_address'))->toBeTrue();

        $filled = Validator::make(
            ['proposed_company_name' => 'PT Cukup', 'proposed_company_address' => 'Jl. Cukup 1'],
            Arr::only($form->rules(), ['proposed_company_name', 'proposed_company_address'])
        );

        expect($filled->fails())->toBeFalse();

        $placing = aaShapeForm(['use_placement' => true]);

        expect(array_keys($placing->rules()))->toContain('placement_id')
            ->and(array_keys($placing->rules()))->not->toContain('proposed_company_name');
    });

    test('920SO-FR-APPLY-027: proposal mode flattens with the company kept', function (): void {
        $form = aaShapeForm([
            'name' => 'Shape Kid',
            'email' => 'shape-propose@example.com',
            'phone' => '0813',
            'address' => 'Jl. Shape 2',
            'national_id_number' => 'NID-10',
            'student_id_number' => 'NIS-10',
            'department_id' => null,
            'class_name' => 'XII-TKJ-1',
            'entry_year' => null,
            'internship_id' => 'intern-2',
            'placement_id' => 'place-stale',
            'academic_year' => '2025/2026',
            'proposed_company_name' => 'PT Baru',
            'proposed_company_address' => 'Jl. Baru 3',
            'use_placement' => false,
        ]);

        $flat = $form->toArray();

        expect($flat['placement_id'])->toBeNull()
            ->and($flat['proposed_company_name'])->toBe('PT Baru')
            ->and($flat['proposed_company_address'])->toBe('Jl. Baru 3')
            ->and($flat['entry_year'])->toBeNull()
            ->and($flat['department_id'])->toBeNull()
            ->and(array_keys($flat))->toBe([
                'name', 'email', 'phone', 'address', 'national_id_number', 'student_id_number',
                'department_id', 'class_name', 'entry_year', 'internship_id', 'placement_id',
                'academic_year', 'proposed_company_name', 'proposed_company_address',
            ]);
    });
});

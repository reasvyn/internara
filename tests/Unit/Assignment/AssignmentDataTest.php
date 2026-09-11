<?php

declare(strict_types=1);

use App\Modules\Assignment\Data\CreateAssignmentData;
use App\Modules\Assignment\Data\UpdateAssignmentData;

describe('T657Z: CreateAssignmentData DTO', function (): void {
    test('T657Z-FR-ASG-002: fromArray maps exact keys with mandatory and date defaults', function (): void {
        $dto = CreateAssignmentData::fromArray([
            'assignmentType' => 'project',
            'internshipId' => 'intern-1',
            'title' => 'Laporan Mingguan',
        ]);

        expect($dto->assignmentType)->toBe('project');
        expect($dto->internshipId)->toBe('intern-1');
        expect($dto->title)->toBe('Laporan Mingguan');
        expect($dto->description)->toBeNull();
        expect($dto->isMandatory)->toBeFalse();
        expect($dto->dueDate)->toBeNull();
    });

    test('T657Z-FR-ASG-002: fromArray accepts snake_case keys', function (): void {
        $dto = CreateAssignmentData::fromArray([
            'assignment_type' => 'report',
            'internship_id' => 'intern-2',
            'title' => 'T',
            'is_mandatory' => true,
            'due_date' => '2026-08-01',
        ]);

        expect($dto->assignmentType)->toBe('report');
        expect($dto->internshipId)->toBe('intern-2');
        expect($dto->isMandatory)->toBeTrue();
        expect($dto->dueDate)->toBe('2026-08-01');
    });

    test('T657Z-FR-ASG-001: fromArray throws when the title is missing', function (): void {
        expect(fn (): CreateAssignmentData => CreateAssignmentData::fromArray([
            'assignmentType' => 'project',
            'internshipId' => 'intern-1',
        ]))->toThrow(InvalidArgumentException::class, 'title');
    });

    test('T657Z-FR-ASG-001: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['assignmentType' => 'essay', 'internshipId' => 'i', 'title' => 'Esei'];

        expect(CreateAssignmentData::from($payload)->title)->toBe('Esei');

        $source = new class
        {
            public function toArray(): array
            {
                return ['assignmentType' => 'project', 'internshipId' => 'i2', 'title' => 'P2'];
            }
        };

        expect(CreateAssignmentData::from($source)->internshipId)->toBe('i2');
        expect(fn (): CreateAssignmentData => CreateAssignmentData::from(9))->toThrow(InvalidArgumentException::class);
    });

    test('T657Z-FR-ASG-001: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new CreateAssignmentData(assignmentType: 'project', internshipId: 'i', title: 'T');

        expect($dto->toArray())->toBe([
            'assignmentType' => 'project',
            'internshipId' => 'i',
            'title' => 'T',
            'description' => null,
            'isMandatory' => false,
            'dueDate' => null,
        ]);
        expect($dto->only('title'))->toBe(['title' => 'T']);
        expect(array_key_exists('dueDate', $dto->except('title')))->toBeTrue();

        $merged = $dto->merge(['isMandatory' => true]);

        expect($merged->isMandatory)->toBeTrue();
        expect($dto->isMandatory)->toBeFalse();
    });
});

describe('T657Z: UpdateAssignmentData DTO', function (): void {
    test('T657Z-FR-ASG-004: fromArray defaults every field to null for partial updates', function (): void {
        $dto = UpdateAssignmentData::fromArray([]);

        expect($dto->assignmentType)->toBeNull();
        expect($dto->title)->toBeNull();
        expect($dto->description)->toBeNull();
        expect($dto->isMandatory)->toBeNull();
        expect($dto->dueDate)->toBeNull();
    });

    test('T657Z-FR-ASG-004: fromArray accepts snake_case keys', function (): void {
        $dto = UpdateAssignmentData::fromArray(['assignment_type' => 'report', 'is_mandatory' => true]);

        expect($dto->assignmentType)->toBe('report');
        expect($dto->isMandatory)->toBeTrue();
        expect($dto->title)->toBeNull();
    });

    test('T657Z-FR-ASG-004: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(UpdateAssignmentData::from(['title' => 'Baru'])->title)->toBe('Baru');

        $source = new class
        {
            public function toArray(): array
            {
                return ['dueDate' => '2026-09-01'];
            }
        };

        expect(UpdateAssignmentData::from($source)->dueDate)->toBe('2026-09-01');
        expect(fn (): UpdateAssignmentData => UpdateAssignmentData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('T657Z-FR-ASG-004: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new UpdateAssignmentData(title: 'Lama');

        expect($dto->toArray())->toBe([
            'assignmentType' => null,
            'title' => 'Lama',
            'description' => null,
            'isMandatory' => null,
            'dueDate' => null,
        ]);
        expect($dto->only('title'))->toBe(['title' => 'Lama']);
        expect(array_key_exists('dueDate', $dto->except('title')))->toBeTrue();

        $merged = $dto->merge(['title' => 'Baru']);

        expect($merged->title)->toBe('Baru');
        expect($dto->title)->toBe('Lama');
    });
});

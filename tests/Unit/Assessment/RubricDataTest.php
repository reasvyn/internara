<?php

declare(strict_types=1);

use App\Modules\Assessment\Domain\Rubric\Data\CreateCompetencyData;
use App\Modules\Assessment\Domain\Rubric\Data\CreateIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Data\CreateRubricData;
use App\Modules\Assessment\Domain\Rubric\Data\DeleteIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Data\UpdateCompetencyData;
use App\Modules\Assessment\Domain\Rubric\Data\UpdateIndicatorData;
use App\Modules\Assessment\Domain\Rubric\Data\UpdateRubricData;

describe('ARDA6: CreateCompetencyData DTO', function (): void {
    test('ARDA6-FR-ASM-002: fromArray maps exact keys with role and order defaults', function (): void {
        $dto = CreateCompetencyData::fromArray(['name' => 'Kedisiplinan']);

        expect($dto->name)->toBe('Kedisiplinan');
        expect($dto->description)->toBeNull();
        expect($dto->weight)->toBe(0);
        expect($dto->evaluatorRole)->toBe('teacher');
        expect($dto->order)->toBe(0);
    });

    test('ARDA6-FR-ASM-002: fromArray accepts snake_case keys', function (): void {
        $dto = CreateCompetencyData::fromArray(['name' => 'K', 'evaluator_role' => 'supervisor', 'weight' => 30]);

        expect($dto->evaluatorRole)->toBe('supervisor');
        expect($dto->weight)->toBe(30);
    });

    test('ARDA6-FR-ASM-002: fromArray throws when the name is missing', function (): void {
        expect(fn (): CreateCompetencyData => CreateCompetencyData::fromArray(['weight' => 10]))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('ARDA6-FR-ASM-002: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(CreateCompetencyData::from(['name' => 'K'])->name)->toBe('K');

        $source = new class
        {
            public function toArray(): array
            {
                return ['name' => 'K2', 'order' => 2];
            }
        };

        expect(CreateCompetencyData::from($source)->order)->toBe(2);
        expect(fn (): CreateCompetencyData => CreateCompetencyData::from(null))->toThrow(InvalidArgumentException::class);
    });

    test('ARDA6-FR-ASM-002: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new CreateCompetencyData(name: 'K', weight: 25);

        expect($dto->toArray())->toBe([
            'name' => 'K',
            'description' => null,
            'weight' => 25,
            'evaluatorRole' => 'teacher',
            'order' => 0,
        ]);
        expect($dto->only('name', 'weight'))->toBe(['name' => 'K', 'weight' => 25]);
        expect($dto->except('description', 'order'))->toBe([
            'name' => 'K',
            'weight' => 25,
            'evaluatorRole' => 'teacher',
        ]);

        $merged = $dto->merge(['evaluatorRole' => 'supervisor']);

        expect($merged->evaluatorRole)->toBe('supervisor');
        expect($dto->evaluatorRole)->toBe('teacher');
    });
});

describe('ARDA6: CreateIndicatorData DTO', function (): void {
    test('ARDA6-FR-ASM-003: fromArray maps exact keys with score and weight defaults', function (): void {
        $dto = CreateIndicatorData::fromArray(['competencyId' => 'c-1', 'name' => 'Hadir tepat waktu']);

        expect($dto->competencyId)->toBe('c-1');
        expect($dto->name)->toBe('Hadir tepat waktu');
        expect($dto->maxScore)->toBe(100);
        expect($dto->weight)->toBe(0);
        expect($dto->order)->toBe(0);
    });

    test('ARDA6-FR-ASM-003: fromArray accepts snake_case keys', function (): void {
        $dto = CreateIndicatorData::fromArray(['competency_id' => 'c-9', 'name' => 'N', 'max_score' => 50]);

        expect($dto->competencyId)->toBe('c-9');
        expect($dto->maxScore)->toBe(50);
    });

    test('ARDA6-FR-ASM-003: fromArray throws when the competency reference is missing', function (): void {
        expect(fn (): CreateIndicatorData => CreateIndicatorData::fromArray(['name' => 'N']))
            ->toThrow(InvalidArgumentException::class, 'competencyId');
    });

    test('ARDA6-FR-ASM-003: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(CreateIndicatorData::from(['competencyId' => 'c', 'name' => 'N'])->competencyId)->toBe('c');

        $source = new class
        {
            public function toArray(): array
            {
                return ['competencyId' => 'c2', 'name' => 'N2', 'weight' => 40];
            }
        };

        expect(CreateIndicatorData::from($source)->weight)->toBe(40);
        expect(fn (): CreateIndicatorData => CreateIndicatorData::from(7.5))->toThrow(InvalidArgumentException::class);
    });

    test('ARDA6-FR-ASM-003: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new CreateIndicatorData(competencyId: 'c-1', name: 'N', maxScore: 80);

        expect($dto->toArray()['maxScore'])->toBe(80);
        expect($dto->only('competencyId', 'name'))->toBe(['competencyId' => 'c-1', 'name' => 'N']);
        expect(array_key_exists('order', $dto->except('maxScore')))->toBeTrue();

        $merged = $dto->merge(['maxScore' => 90]);

        expect($merged->maxScore)->toBe(90);
        expect($dto->maxScore)->toBe(80);
    });
});

describe('ARDA6: CreateRubricData DTO', function (): void {
    test('ARDA6-FR-ASM-001: fromArray maps exact keys defaulting to active', function (): void {
        $dto = CreateRubricData::fromArray(['name' => 'Rubrik PKL 2026']);

        expect($dto->name)->toBe('Rubrik PKL 2026');
        expect($dto->description)->toBeNull();
        expect($dto->isActive)->toBeTrue();
    });

    test('ARDA6-FR-ASM-001: fromArray accepts snake_case keys', function (): void {
        $dto = CreateRubricData::fromArray(['name' => 'R', 'is_active' => false]);

        expect($dto->isActive)->toBeFalse();
    });

    test('ARDA6-FR-ASM-001: fromArray throws when the name is missing', function (): void {
        expect(fn (): CreateRubricData => CreateRubricData::fromArray([]))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('ARDA6-FR-ASM-001: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(CreateRubricData::from(['name' => 'R'])->isActive)->toBeTrue();

        $source = new class
        {
            public function toArray(): array
            {
                return ['name' => 'R2', 'description' => 'desc'];
            }
        };

        expect(CreateRubricData::from($source)->description)->toBe('desc');
        expect(fn (): CreateRubricData => CreateRubricData::from(true))->toThrow(InvalidArgumentException::class);
    });

    test('ARDA6-FR-ASM-001: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new CreateRubricData(name: 'R');

        expect($dto->toArray())->toBe(['name' => 'R', 'description' => null, 'isActive' => true]);
        expect($dto->only('isActive'))->toBe(['isActive' => true]);
        expect($dto->except('description'))->toBe(['name' => 'R', 'isActive' => true]);

        $merged = $dto->merge(['isActive' => false]);

        expect($merged->isActive)->toBeFalse();
        expect($dto->isActive)->toBeTrue();
    });
});

describe('ARDA6: DeleteIndicatorData DTO', function (): void {
    test('ARDA6-FR-ASM-004: fromArray maps both structure references', function (): void {
        $dto = DeleteIndicatorData::fromArray(['competencyId' => 'c-1', 'indicatorId' => 'i-1']);

        expect($dto->competencyId)->toBe('c-1');
        expect($dto->indicatorId)->toBe('i-1');
    });

    test('ARDA6-FR-ASM-004: fromArray accepts snake_case keys', function (): void {
        $dto = DeleteIndicatorData::fromArray(['competency_id' => 'c-2', 'indicator_id' => 'i-2']);

        expect($dto->competencyId)->toBe('c-2');
        expect($dto->indicatorId)->toBe('i-2');
    });

    test('ARDA6-FR-ASM-004: fromArray throws when a reference is missing', function (): void {
        expect(fn (): DeleteIndicatorData => DeleteIndicatorData::fromArray(['competencyId' => 'c-1']))
            ->toThrow(InvalidArgumentException::class, 'indicatorId');
    });

    test('ARDA6-FR-ASM-004: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(DeleteIndicatorData::from(['competencyId' => 'c', 'indicatorId' => 'i'])->indicatorId)->toBe('i');

        $source = new class
        {
            public function toArray(): array
            {
                return ['competencyId' => 'cx', 'indicatorId' => 'ix'];
            }
        };

        expect(DeleteIndicatorData::from($source)->competencyId)->toBe('cx');
        expect(fn (): DeleteIndicatorData => DeleteIndicatorData::from([]))->toThrow(InvalidArgumentException::class);
    });

    test('ARDA6-FR-ASM-004: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new DeleteIndicatorData(competencyId: 'c-1', indicatorId: 'i-1');

        expect($dto->toArray())->toBe(['competencyId' => 'c-1', 'indicatorId' => 'i-1']);
        expect($dto->only('indicatorId'))->toBe(['indicatorId' => 'i-1']);
        expect($dto->except('competencyId'))->toBe(['indicatorId' => 'i-1']);

        $merged = $dto->merge(['indicatorId' => 'i-2']);

        expect($merged->indicatorId)->toBe('i-2');
        expect($dto->indicatorId)->toBe('i-1');
    });
});

describe('ARDA6: UpdateCompetencyData DTO', function (): void {
    test('ARDA6-FR-ASM-002: fromArray maps exact keys with defaults', function (): void {
        $dto = UpdateCompetencyData::fromArray(['competencyId' => 'c-1', 'name' => 'Kerapian']);

        expect($dto->competencyId)->toBe('c-1');
        expect($dto->name)->toBe('Kerapian');
        expect($dto->evaluatorRole)->toBe('teacher');
        expect($dto->weight)->toBe(0);
        expect($dto->order)->toBe(0);
    });

    test('ARDA6-FR-ASM-002: fromArray accepts snake_case keys', function (): void {
        $dto = UpdateCompetencyData::fromArray(['competency_id' => 'c-3', 'name' => 'K', 'evaluator_role' => 'supervisor']);

        expect($dto->competencyId)->toBe('c-3');
        expect($dto->evaluatorRole)->toBe('supervisor');
    });

    test('ARDA6-FR-ASM-002: fromArray throws when the name is missing', function (): void {
        expect(fn (): UpdateCompetencyData => UpdateCompetencyData::fromArray(['competencyId' => 'c-1']))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('ARDA6-FR-ASM-002: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(UpdateCompetencyData::from(['competencyId' => 'c', 'name' => 'N'])->name)->toBe('N');

        $source = new class
        {
            public function toArray(): array
            {
                return ['competencyId' => 'c4', 'name' => 'N4', 'weight' => 15];
            }
        };

        expect(UpdateCompetencyData::from($source)->weight)->toBe(15);
        expect(fn (): UpdateCompetencyData => UpdateCompetencyData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('ARDA6-FR-ASM-002: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new UpdateCompetencyData(competencyId: 'c-1', name: 'K');

        expect($dto->toArray()['competencyId'])->toBe('c-1');
        expect($dto->only('competencyId', 'name'))->toBe(['competencyId' => 'c-1', 'name' => 'K']);
        expect(array_key_exists('evaluatorRole', $dto->except('weight')))->toBeTrue();

        $merged = $dto->merge(['weight' => 20]);

        expect($merged->weight)->toBe(20);
        expect($dto->weight)->toBe(0);
    });
});

describe('ARDA6: UpdateIndicatorData DTO', function (): void {
    test('ARDA6-FR-ASM-003: fromArray maps exact keys with score defaults', function (): void {
        $dto = UpdateIndicatorData::fromArray(['competencyId' => 'c-1', 'indicatorId' => 'i-1', 'name' => 'N']);

        expect($dto->indicatorId)->toBe('i-1');
        expect($dto->maxScore)->toBe(100);
        expect($dto->weight)->toBe(0);
        expect($dto->order)->toBe(0);
    });

    test('ARDA6-FR-ASM-003: fromArray accepts snake_case keys', function (): void {
        $dto = UpdateIndicatorData::fromArray([
            'competency_id' => 'c-1',
            'indicator_id' => 'i-7',
            'name' => 'N',
            'max_score' => 60,
        ]);

        expect($dto->indicatorId)->toBe('i-7');
        expect($dto->maxScore)->toBe(60);
    });

    test('ARDA6-FR-ASM-003: fromArray throws when the indicator reference is missing', function (): void {
        expect(fn (): UpdateIndicatorData => UpdateIndicatorData::fromArray(['competencyId' => 'c-1', 'name' => 'N']))
            ->toThrow(InvalidArgumentException::class, 'indicatorId');
    });

    test('ARDA6-FR-ASM-003: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['competencyId' => 'c', 'indicatorId' => 'i', 'name' => 'N'];

        expect(UpdateIndicatorData::from($payload)->indicatorId)->toBe('i');

        $source = new class
        {
            public function toArray(): array
            {
                return ['competencyId' => 'c', 'indicatorId' => 'i9', 'name' => 'N9'];
            }
        };

        expect(UpdateIndicatorData::from($source)->indicatorId)->toBe('i9');
        expect(fn (): UpdateIndicatorData => UpdateIndicatorData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('ARDA6-FR-ASM-003: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new UpdateIndicatorData(competencyId: 'c-1', indicatorId: 'i-1', name: 'N');

        expect($dto->toArray()['maxScore'])->toBe(100);
        expect($dto->only('indicatorId'))->toBe(['indicatorId' => 'i-1']);
        expect(array_key_exists('name', $dto->except('indicatorId')))->toBeTrue();

        $merged = $dto->merge(['name' => 'Renamed']);

        expect($merged->name)->toBe('Renamed');
        expect($dto->name)->toBe('N');
    });
});

describe('ARDA6: UpdateRubricData DTO', function (): void {
    test('ARDA6-FR-ASM-001: fromArray maps exact keys defaulting to active', function (): void {
        $dto = UpdateRubricData::fromArray(['name' => 'Rubrik Revisi']);

        expect($dto->name)->toBe('Rubrik Revisi');
        expect($dto->isActive)->toBeTrue();
    });

    test('ARDA6-FR-ASM-001: fromArray accepts snake_case keys', function (): void {
        $dto = UpdateRubricData::fromArray(['name' => 'R', 'is_active' => false]);

        expect($dto->isActive)->toBeFalse();
    });

    test('ARDA6-FR-ASM-001: fromArray throws when the name is missing', function (): void {
        expect(fn (): UpdateRubricData => UpdateRubricData::fromArray(['isActive' => false]))
            ->toThrow(InvalidArgumentException::class, 'name');
    });

    test('ARDA6-FR-ASM-001: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(UpdateRubricData::from(['name' => 'R'])->name)->toBe('R');

        $source = new class
        {
            public function toArray(): array
            {
                return ['name' => 'R2', 'isActive' => false];
            }
        };

        expect(UpdateRubricData::from($source)->isActive)->toBeFalse();
        expect(fn (): UpdateRubricData => UpdateRubricData::from([]))->toThrow(InvalidArgumentException::class);
    });

    test('ARDA6-FR-ASM-001: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new UpdateRubricData(name: 'R', description: 'd');

        expect($dto->toArray())->toBe(['name' => 'R', 'description' => 'd', 'isActive' => true]);
        expect($dto->only('description'))->toBe(['description' => 'd']);
        expect($dto->except('description'))->toBe(['name' => 'R', 'isActive' => true]);

        $merged = $dto->merge(['description' => 'd2']);

        expect($merged->description)->toBe('d2');
        expect($dto->description)->toBe('d');
    });
});

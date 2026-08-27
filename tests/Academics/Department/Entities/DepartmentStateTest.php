<?php

declare(strict_types=1);

use App\Academics\Department\Entities\DepartmentState;
use App\Academics\Department\Models\Department;
use App\Core\Entities\BaseEntity;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 4HWSB — Department Management — DepartmentState (spec-driven)
|--------------------------------------------------------------------------
*/

describe('4HWSB: DepartmentState', function (): void {
    test('4HWSB-FR-DM6: is final readonly and extends BaseEntity', function (): void {
        $ref = new ReflectionClass(DepartmentState::class);

        expect($ref->isFinal())->toBeTrue()
            ->and($ref->isReadOnly())->toBeTrue()
            ->and($ref->getParentClass()?->getName())->toBe(BaseEntity::class);
    });

    test('4HWSB-FR-DM7: fromModel() computes profileCount via count', function (): void {
        $dept = Department::factory()->create();
        // No profiles yet
        $state = DepartmentState::fromModel($dept);

        expect($state)->toBeInstanceOf(DepartmentState::class);

        // Create a profile for this department
        \App\User\Profile\Models\Profile::factory()->create(['department_id' => $dept->id]);
        $dept->refresh();
        // Use withCount to simulate eager count
        $deptWithCount = Department::withCount('profiles')->find($dept->id);
        $state2 = DepartmentState::fromModel($deptWithCount);

        $ref = new ReflectionClass($state2);
        $prop = $ref->getProperty('profileCount');
        $prop->setAccessible(true);
        expect($prop->getValue($state2))->toBe(1);
    });

    test('4HWSB-FR-DM8: fromModel() computes hasProfiles via exists() or eager check', function (): void {
        $dept = Department::factory()->create();
        $state = DepartmentState::fromModel($dept);
        $ref = new ReflectionClass($state);
        $prop = $ref->getProperty('hasProfiles');
        $prop->setAccessible(true);
        expect($prop->getValue($state))->toBeFalse();

        \App\User\Profile\Models\Profile::factory()->create(['department_id' => $dept->id]);
        $dept2 = Department::with('profiles')->find($dept->id);
        $state2 = DepartmentState::fromModel($dept2);
        $prop2 = (new ReflectionClass($state2))->getProperty('hasProfiles');
        $prop2->setAccessible(true);
        expect($prop2->getValue($state2))->toBeTrue();
    });

    test('4HWSB-FR-DM9: canBeDeleted() returns false when hasProfiles is true', function (): void {
        $dept = Department::factory()->create();
        \App\User\Profile\Models\Profile::factory()->create(['department_id' => $dept->id]);
        $deptWithProfiles = Department::with('profiles')->find($dept->id);
        $state = DepartmentState::fromModel($deptWithProfiles);

        expect($state->canBeDeleted())->toBeFalse();
    });

    test('4HWSB-FR-DM10: canBeDeleted() returns true when hasProfiles is false', function (): void {
        $dept = Department::factory()->create();
        $state = DepartmentState::fromModel($dept);

        expect($state->canBeDeleted())->toBeTrue();
    });
});

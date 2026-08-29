<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\Department\Actions\CreateDepartmentAction;
use App\Modules\Academics\Domain\Department\Events\DepartmentCreated;
use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Core\Actions\BaseCommandAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 4HWSB — Department Management — CreateDepartmentAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('4HWSB: CreateDepartmentAction', function (): void {
    test('4HWSB-FR-DM11: extends BaseCommandAction', function (): void {
        expect(new CreateDepartmentAction)->toBeInstanceOf(BaseCommandAction::class);
    });

    test('4HWSB-FR-DM11: validates name is required and unique', function (): void {
        Department::factory()->create(['name' => 'Existing Dept']);

        expect(fn () => app(CreateDepartmentAction::class)->execute(['name' => 'Existing Dept']))
            ->toThrow(Illuminate\Validation\ValidationException::class);

        expect(fn () => app(CreateDepartmentAction::class)->execute(['description' => 'No name']))
            ->toThrow(Illuminate\Validation\ValidationException::class);
    });

    test('4HWSB-FR-DM11: validates name max 100 and description max 500', function (): void {
        expect(fn () => app(CreateDepartmentAction::class)->execute(['name' => str_repeat('a', 101)]))
            ->toThrow(Illuminate\Validation\ValidationException::class);

        expect(fn () => app(CreateDepartmentAction::class)->execute([
            'name' => 'Valid Name',
            'description' => str_repeat('a', 501),
        ]))->toThrow(Illuminate\Validation\ValidationException::class);
    });

    test('4HWSB-FR-DM12: wraps creation in transaction and creates department', function (): void {
        $source = file_get_contents((new ReflectionClass(CreateDepartmentAction::class))->getFileName());
        expect($source)->toContain('transaction');

        $dept = app(CreateDepartmentAction::class)->execute([
            'name' => 'New Dept',
            'description' => 'Test description',
        ]);

        expect($dept)->toBeInstanceOf(Department::class)
            ->and($dept->name)->toBe('New Dept')
            ->and(Department::where('name', 'New Dept')->exists())->toBeTrue();
    });

    test('4HWSB-FR-DM13: dispatches DepartmentCreated event', function (): void {
        Event::fake([DepartmentCreated::class]);

        $dept = app(CreateDepartmentAction::class)->execute(['name' => 'Event Test']);

        Event::assertDispatched(DepartmentCreated::class, function ($event) use ($dept) {
            return $event->department->id === $dept->id;
        });
    });

    test('4HWSB-FR-DM14: logs creation via activity log', function (): void {
        $source = file_get_contents((new ReflectionClass(CreateDepartmentAction::class))->getFileName());

        expect($source)->toContain("log('department_created'");
    });

    test('4HWSB-FR-DM11: creates with only name and null description', function (): void {
        $dept = app(CreateDepartmentAction::class)->execute(['name' => 'Only Name']);

        expect($dept->name)->toBe('Only Name')
            ->and($dept->description)->toBeNull();
    });
});

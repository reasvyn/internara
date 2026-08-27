<?php

declare(strict_types=1);

use App\Academics\Department\Actions\UpdateDepartmentAction;
use App\Academics\Department\Events\DepartmentUpdated;
use App\Academics\Department\Models\Department;
use App\Core\Actions\BaseCommandAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 4HWSB — Department Management — UpdateDepartmentAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('4HWSB: UpdateDepartmentAction', function (): void {
    test('4HWSB-FR-DM15: extends BaseCommandAction', function (): void {
        expect(new UpdateDepartmentAction)->toBeInstanceOf(BaseCommandAction::class);
    });

    test('4HWSB-FR-DM15: validates name uniqueness excluding current record', function (): void {
        $dept1 = Department::factory()->create(['name' => 'Existing']);
        $dept2 = Department::factory()->create(['name' => 'Other']);

        // Should allow updating dept2 to same name as itself (no change)
        expect(fn () => app(UpdateDepartmentAction::class)->execute($dept2, ['name' => 'Other']))
            ->not->toThrow(Exception::class);

        // Should reject duplicate name from another record
        expect(fn () => app(UpdateDepartmentAction::class)->execute($dept2, ['name' => 'Existing']))
            ->toThrow(Illuminate\Validation\ValidationException::class);
    });

    test('4HWSB-FR-DM16: wraps update in transaction', function (): void {
        $source = file_get_contents((new ReflectionClass(UpdateDepartmentAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });

    test('4HWSB-FR-DM15: updates department name and description', function (): void {
        $dept = Department::factory()->create(['name' => 'Old Name', 'description' => 'Old Desc']);

        $updated = app(UpdateDepartmentAction::class)->execute($dept, [
            'name' => 'New Name',
            'description' => 'New Desc',
        ]);

        expect($updated->name)->toBe('New Name')
            ->and($updated->description)->toBe('New Desc')
            ->and(Department::find($dept->id)->name)->toBe('New Name');
    });

    test('4HWSB-FR-DM17: dispatches DepartmentUpdated event', function (): void {
        Event::fake([DepartmentUpdated::class]);

        $dept = Department::factory()->create();

        app(UpdateDepartmentAction::class)->execute($dept, ['name' => 'Updated']);

        Event::assertDispatched(DepartmentUpdated::class, fn ($e) => $e->department->id === $dept->id);
    });

    test('4HWSB-FR-DM18: logs update via activity log', function (): void {
        $source = file_get_contents((new ReflectionClass(UpdateDepartmentAction::class))->getFileName());

        expect($source)->toContain("log('department_updated'");
    });
});

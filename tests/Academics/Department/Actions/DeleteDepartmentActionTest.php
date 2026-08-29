<?php

declare(strict_types=1);

use App\Modules\Academics\Domain\Department\Actions\DeleteDepartmentAction;
use App\Modules\Academics\Domain\Department\Events\DepartmentDeleted;
use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Domain\Profile\Models\Profile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 4HWSB — Department Management — DeleteDepartmentAction (spec-driven)
|--------------------------------------------------------------------------
*/

describe('4HWSB: DeleteDepartmentAction', function (): void {
    test('4HWSB-FR-DM19: extends BaseCommandAction', function (): void {
        expect(new DeleteDepartmentAction)->toBeInstanceOf(BaseCommandAction::class);
    });

    test('4HWSB-FR-DM19: checks profiles()->count() > 0 before deleting (source)', function (): void {
        $source = file_get_contents((new ReflectionClass(DeleteDepartmentAction::class))->getFileName());

        expect($source)->toContain('profiles()->exists()');
    });

    test('4HWSB-FR-DM20: throws RejectedException when profiles are assigned', function (): void {
        $dept = Department::factory()->create();
        Profile::factory()->create(['department_id' => $dept->id]);

        expect(fn () => app(DeleteDepartmentAction::class)->execute($dept))
            ->toThrow(RejectedException::class);
    });

    test('4HWSB-FR-DM19: deletes department when no profiles assigned', function (): void {
        $dept = Department::factory()->create();

        app(DeleteDepartmentAction::class)->execute($dept);

        expect(Department::find($dept->id))->toBeNull();
    });

    test('4HWSB-FR-DM21: wraps deletion in transaction', function (): void {
        $source = file_get_contents((new ReflectionClass(DeleteDepartmentAction::class))->getFileName());

        expect($source)->toContain('transaction');
    });

    test('4HWSB-FR-DM22: dispatches DepartmentDeleted event', function (): void {
        Event::fake([DepartmentDeleted::class]);

        $dept = Department::factory()->create();

        app(DeleteDepartmentAction::class)->execute($dept);

        Event::assertDispatched(DepartmentDeleted::class, fn ($e) => $e->department->id === $dept->id);
    });

    test('4HWSB-FR-DM23: logs deletion via activity log', function (): void {
        $source = file_get_contents((new ReflectionClass(DeleteDepartmentAction::class))->getFileName());

        expect($source)->toContain("log('department_deleted'");
    });
});

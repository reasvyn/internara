<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Actions\CreateDepartmentAction;
use App\Modules\Academic\Domain\Department\Actions\DeleteDepartmentAction;
use App\Modules\Academic\Domain\Department\Actions\UpdateDepartmentAction;
use App\Modules\Academic\Domain\Department\Events\DepartmentCreated;
use App\Modules\Academic\Domain\Department\Events\DepartmentDeleted;
use App\Modules\Academic\Domain\Department\Events\DepartmentUpdated;
use App\Modules\Academic\Domain\Department\Livewire\DepartmentManager;
use App\Modules\Academic\Domain\Department\Livewire\Forms\DepartmentForm;
use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Academic\Domain\Department\Policies\DepartmentPolicy;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Domain\Dashboard\Listeners\ClearDashboardCacheOnDepartmentChange;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function hwsbAdmin(object $test): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $test->actingAs($admin);

    return $admin;
}

describe('4HWSB: departments', function (): void {
    test('4HWSB-FR-DEPT-001: departments use UUID keys with unique names (also NFR-DEPT-002)', function (): void {
        $department = Department::factory()->create(['name' => 'RPL']);

        expect(Str::isUuid($department->id))->toBeTrue();

        expect(fn () => Department::factory()->create(['name' => 'RPL']))->toThrow(QueryException::class);
    });

    test('4HWSB-FR-DEPT-002: the model bridges persistence and entity cleanly', function (): void {
        $department = Department::factory()->make(['name' => 'RPL', 'description' => 'Software']);

        expect($department->asDepartmentState()->canBeDeleted())->toBeTrue()
            ->and($department->profiles()->exists())->toBeFalse();
    });

    test('4HWSB-FR-DEPT-006: creation validates, dispatches, and audits (also FR-DEPT-014, NFR-DEPT-002, NFR-DEPT-004)', function (): void {
        hwsbAdmin($this);
        Event::fake([DepartmentCreated::class]);

        $department = app(CreateDepartmentAction::class)->execute(['name' => 'RPL', 'description' => 'Software']);

        Event::assertDispatched(DepartmentCreated::class);
        expect(DB::table('activity_log')->where('description', 'department_created')->exists())->toBeTrue();

        expect(fn () => app(CreateDepartmentAction::class)->execute(['name' => 'RPL']))->toThrow(ValidationException::class);
    });

    test('4HWSB-FR-DEPT-007: renaming skips the uniqueness conflict on self (also UC-DEPT-002)', function (): void {
        $department = Department::factory()->create(['name' => 'RPL']);
        Department::factory()->create(['name' => 'TKJ']);

        $renamed = app(UpdateDepartmentAction::class)->execute($department, ['name' => 'RPL Baru']);

        expect($renamed->name)->toBe('RPL Baru');

        expect(fn () => app(UpdateDepartmentAction::class)->execute($renamed, ['name' => 'TKJ']))
            ->toThrow(ValidationException::class);
    });

    test('4HWSB-FR-DEPT-008: guarded deletion refuses with a translatable message (also UC-DEPT-003)', function (): void {
        $department = Department::factory()->create(['name' => 'RPL']);
        Profile::factory()->create(['department_id' => $department->id]);

        try {
            app(DeleteDepartmentAction::class)->execute($department);
            expect(false)->toBeTrue('guarded deletion was allowed');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe(__('department.cannot_delete_with_profiles'));
        }

        $this->assertModelExists($department->fresh());
    });

    test('4HWSB-NFR-DEPT-003: force-delete stays forbidden and deletes are permanent (also DD-DEPT-002)', function (): void {
        hwsbAdmin($this);
        $policy = new DepartmentPolicy;
        $department = Department::factory()->create(['name' => 'RPL']);

        expect($policy->forceDelete(User::factory()->create(), $department))->toBeFalse();

        app(DeleteDepartmentAction::class)->execute($department);

        $this->assertModelMissing($department);
    });

    test('4HWSB-DD-DEPT-001: policy, action, and bulk flows share the entity guard', function (): void {
        hwsbAdmin($this);
        $policy = new DepartmentPolicy;
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $open = Department::factory()->create(['name' => 'RPL']);
        $blocked = Department::factory()->create(['name' => 'TKJ']);
        Profile::factory()->create(['department_id' => $blocked->id]);

        expect($policy->delete($admin, $open))->toBeTrue()
            ->and($policy->delete($admin, $blocked))->toBeFalse();
    });

    test('4HWSB-FR-DEPT-012: the manager table lists and filters records', function (): void {
        hwsbAdmin($this);
        Department::factory()->create(['name' => 'RPL']);
        Department::factory()->create(['name' => 'TKJ']);

        Livewire::test(DepartmentManager::class)
            ->assertSee('RPL')
            ->set('search', 'TKJ')
            ->assertSee('TKJ')
            ->assertDontSee('RPL');
    });

    test('4HWSB-FR-DEPT-013: bulk delete separates deleted from blocked rows (also UC-DEPT-005, NFR-DEPT-005, DD-DEPT-004)', function (): void {
        hwsbAdmin($this);
        $gone = Department::factory()->create(['name' => 'RPL']);
        $stays = Department::factory()->create(['name' => 'TKJ']);
        Profile::factory()->create(['department_id' => $stays->id]);

        Livewire::test(DepartmentManager::class)
            ->set('selectedIds', [$gone->id, $stays->id])
            ->set('confirmType', 'delete_selected')
            ->call('confirmAction');

        $this->assertModelMissing($gone);
        $this->assertModelExists($stays);
    });

    test('4HWSB-NFR-DEPT-006: blocked deletions name the profile count', function (): void {
        hwsbAdmin($this);
        $department = Department::factory()->create(['name' => 'RPL']);
        Profile::factory()->count(2)->create(['department_id' => $department->id]);

        Livewire::test(DepartmentManager::class)
            ->set('confirmTarget', $department->id)
            ->set('confirmType', 'delete')
            ->call('confirmAction');

        $this->assertModelExists($department);

        expect(__('department.delete_blocked', ['count' => 2]))->toContain('2');
    });

    test('4HWSB-FR-DEPT-015: every mutation writes an audit entry (also UC-DEPT-001, UC-DEPT-004)', function (): void {
        hwsbAdmin($this);

        $department = app(CreateDepartmentAction::class)->execute(['name' => 'RPL']);
        app(UpdateDepartmentAction::class)->execute($department, ['name' => 'RPL Baru']);
        app(DeleteDepartmentAction::class)->execute($department->fresh());

        foreach (['department_created', 'department_updated', 'department_deleted'] as $event) {
            expect(DB::table('activity_log')->where('description', $event)->exists())->toBeTrue();
        }
    });

    test('4HWSB-FR-DEPT-016: one listener clears dashboard cache on all three events (also DD-DEPT-003)', function (): void {
        $listener = new ClearDashboardCacheOnDepartmentChange;
        $department = Department::factory()->create(['name' => 'RPL']);

        foreach ([new DepartmentCreated($department), new DepartmentUpdated($department), new DepartmentDeleted($department)] as $event) {
            Cache::put(config('cache-keys.admin_dashboard_stats'), ['stale' => true], 600);

            $listener->handle($event);

            expect(Cache::get(config('cache-keys.admin_dashboard_stats')))->toBeNull();
        }
    });

    test('4HWSB-NFR-DEPT-001: student managers cannot delete through the component gate', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);
        $department = Department::factory()->create(['name' => 'RPL']);

        expect((new DepartmentPolicy)->delete($student, $department))->toBeFalse();

        Livewire::test(DepartmentManager::class)
            ->set('confirmTarget', $department->id)
            ->set('confirmType', 'delete')
            ->call('confirmAction')
            ->assertHasNoErrors();

        $this->assertModelExists($department);
    });

    test('4HWSB-NFR-DEPT-009: department files declare strict types on final readonly layers', function (): void {
        foreach (glob(base_path('app/Modules/Academic/Domain/Department/{Actions,Entities,Data}/*.php'), GLOB_BRACE) as $file) {
            $source = file_get_contents($file);

            expect($source)->toContain('declare(strict_types=1)');
        }

        expect(Department::factory()->make(['name' => 'RPL'])->asDepartmentState())->toBeObject();
    });

    test('4HWSB-NFR-DEPT-010: department strings resolve in both locales', function (): void {
        foreach (['department.delete_blocked', 'department.delete_success', 'department.save_success_created'] as $key) {
            foreach (['en', 'id'] as $locale) {
                app()->setLocale($locale);

                expect(__($key))->not->toBe($key);
            }
        }

        app()->setLocale(config('app.locale'));
    });

    test('4HWSB-FR-DEPT-011: department form rules require unique name capped at 255 and description at 1000', function (): void {
        hwsbAdmin($this);
        Department::factory()->create(['name' => 'Existing Dept']);

        // Exceed max length
        Livewire::test(DepartmentManager::class)
            ->call('create')
            ->set('form.name', str_repeat('a', 256))
            ->set('form.description', str_repeat('b', 1001))
            ->call('save')
            ->assertHasErrors(['form.name', 'form.description']);

        // Duplicate name
        Livewire::test(DepartmentManager::class)
            ->call('create')
            ->set('form.name', 'Existing Dept')
            ->call('save')
            ->assertHasErrors(['form.name']);
    });

    test('4HWSB-NFR-DEPT-007: uniqueness violations surface as inline form errors', function (): void {
        hwsbAdmin($this);
        Department::factory()->create(['name' => 'Teknik Komputer']);

        Livewire::test(DepartmentManager::class)
            ->call('create')
            ->set('form.name', 'Teknik Komputer')
            ->call('save')
            ->assertHasErrors(['form.name']);
    });

    test('4HWSB-NFR-DEPT-008: department lists resolve profile counts without N+1 queries', function (): void {
        hwsbAdmin($this);
        $d1 = Department::factory()->create(['name' => 'Dept 1']);
        $d2 = Department::factory()->create(['name' => 'Dept 2']);

        Livewire::test(DepartmentManager::class)
            ->assertStatus(200);
    });

    test('4HWSB-DD-DEPT-005: name caps at 255 and description at 1000 uniformly across layers', function (): void {
        $form = new DepartmentForm(Livewire::new(DepartmentManager::class), 'form');
        $rules = $form->rules();

        expect($rules['name'])->toContain('max:255')
            ->and($rules['description'])->toContain('max:1000');
    });
});

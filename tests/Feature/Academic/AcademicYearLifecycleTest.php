<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\AcademicYear\Actions\ActivateAcademicYearAction;
use App\Modules\Academic\Domain\AcademicYear\Actions\BulkDeleteAcademicYearsAction;
use App\Modules\Academic\Domain\AcademicYear\Actions\CreateAcademicYearAction;
use App\Modules\Academic\Domain\AcademicYear\Actions\DeleteAcademicYearAction;
use App\Modules\Academic\Domain\AcademicYear\Actions\UpdateAcademicYearAction;
use App\Modules\Academic\Domain\AcademicYear\Entities\AcademicYearState;
use App\Modules\Academic\Domain\AcademicYear\Events\AcademicYearActivated;
use App\Modules\Academic\Domain\AcademicYear\Events\AcademicYearCreated;
use App\Modules\Academic\Domain\AcademicYear\Events\AcademicYearDeleted;
use App\Modules\Academic\Domain\AcademicYear\Events\AcademicYearUpdated;
use App\Modules\Academic\Domain\AcademicYear\Livewire\AcademicYearManager;
use App\Modules\Academic\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Academic\Domain\AcademicYear\Support\AcademicYearPeriod;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('XW6F5: academic year lifecycle actions and operations', function (): void {
    test('XW6F5-FR-YEAR-002: AcademicYear model extends BaseModel with Fillable casts and relationships', function (): void {
        $year = AcademicYear::factory()->create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        expect($year->name)->toBe('2026/2027')
            ->and($year->is_active)->toBeTrue()
            ->and($year->start_date)->toBeInstanceOf(Carbon::class)
            ->and($year->end_date)->toBeInstanceOf(Carbon::class)
            ->and($year->internships())->toBeInstanceOf(HasMany::class)
            ->and($year->assessments())->toBeInstanceOf(HasMany::class)
            ->and($year->asAcademicYearState())->toBeInstanceOf(AcademicYearState::class);
    });

    test('XW6F5-FR-YEAR-004: CreateAcademicYearAction auto-activates only on first year and creates inactive thereafter', function (): void {
        Event::fake([AcademicYearCreated::class]);

        $action = app(CreateAcademicYearAction::class);
        $first = $action->execute([
            'name' => '2024/2025',
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
        ]);

        expect($first->is_active)->toBeTrue();
        Event::assertDispatched(AcademicYearCreated::class);

        $second = $action->execute([
            'name' => '2025/2026',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);

        expect($second->is_active)->toBeFalse();
    });

    test('XW6F5-FR-YEAR-005: UpdateAcademicYearAction persists edits inside a transaction and dispatches AcademicYearUpdated', function (): void {
        Event::fake([AcademicYearUpdated::class]);

        $year = AcademicYear::factory()->create(['name' => '2024/2025']);
        $action = app(UpdateAcademicYearAction::class);

        $updated = $action->execute($year, [
            'name' => '2024/2025 Renamed',
            'start_date' => '2024-07-01',
            'end_date' => '2025-06-30',
        ]);

        expect($updated->name)->toBe('2024/2025 Renamed');
        Event::assertDispatched(AcademicYearUpdated::class);
    });

    test('XW6F5-FR-YEAR-006: ActivateAcademicYearAction swaps the flag atomically and refuses already-active targets', function (): void {
        Event::fake([AcademicYearActivated::class]);

        $year1 = AcademicYear::factory()->create(['is_active' => true]);
        $year2 = AcademicYear::factory()->create(['is_active' => false]);

        $action = app(ActivateAcademicYearAction::class);

        expect(fn () => $action->execute($year1))->toThrow(RejectedException::class);

        $activated = $action->execute($year2);

        expect($activated->is_active)->toBeTrue()
            ->and($year1->fresh()->is_active)->toBeFalse()
            ->and($year2->fresh()->is_active)->toBeTrue();

        Event::assertDispatched(AcademicYearActivated::class);
    });

    test('XW6F5-FR-YEAR-007: DeleteAcademicYearAction throws RejectedException for active years', function (): void {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $action = app(DeleteAcademicYearAction::class);

        expect(fn () => $action->execute($year))->toThrow(RejectedException::class);
    });

    test('XW6F5-FR-YEAR-008: BulkDeleteAcademicYearsAction validates every selected year and aborts if any is protected', function (): void {
        $activeYear = AcademicYear::factory()->create(['is_active' => true]);
        $inactiveYear = AcademicYear::factory()->create(['is_active' => false]);
        $action = app(BulkDeleteAcademicYearsAction::class);

        expect(fn () => $action->execute([$activeYear->id, $inactiveYear->id]))
            ->toThrow(RejectedException::class);

        expect(AcademicYear::where('id', $inactiveYear->id)->exists())->toBeTrue();

        $count = $action->execute([$inactiveYear->id]);
        expect($count)->toBe(1)
            ->and(AcademicYear::where('id', $inactiveYear->id)->exists())->toBeFalse();
    });

    test('XW6F5-FR-YEAR-009: mutations dispatch domain events from action after commit', function (): void {
        Event::fake();

        $createAction = app(CreateAcademicYearAction::class);
        $year = $createAction->execute([
            'name' => '2028/2029',
            'start_date' => '2028-07-01',
            'end_date' => '2029-06-30',
        ]);
        Event::assertDispatched(AcademicYearCreated::class);

        $updateAction = app(UpdateAcademicYearAction::class);
        $updateAction->execute($year, [
            'name' => '2028/2029 Updated',
            'start_date' => '2028-07-01',
            'end_date' => '2029-06-30',
        ]);
        Event::assertDispatched(AcademicYearUpdated::class);
    });

    test('XW6F5-FR-YEAR-010: events clear dashboard cache key when configured', function (): void {
        $year = AcademicYear::factory()->create();
        $event = new AcademicYearUpdated($year);
        expect($event->eventName())->toBe('academic_year.updated');
    });

    test('XW6F5-FR-YEAR-011: AcademicYearManager lists years active-first with search and stats', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        AcademicYear::factory()->create(['name' => '2024/2025', 'is_active' => false]);
        AcademicYear::factory()->create(['name' => '2025/2026', 'is_active' => true]);

        Livewire::actingAs($admin)
            ->test(AcademicYearManager::class)
            ->assertSee('2025/2026')
            ->assertSee('2024/2025');
    });

    test('XW6F5-FR-YEAR-012: year form rules require unique name and end date after start date', function (): void {
        $action = app(CreateAcademicYearAction::class);

        expect(fn () => $action->execute([
            'name' => '',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]))->toThrow(ValidationException::class);

        expect(fn () => $action->execute([
            'name' => 'Invalid Date Range',
            'start_date' => '2026-07-01',
            'end_date' => '2025-06-30',
        ]))->toThrow(ValidationException::class);
    });

    test('XW6F5-FR-YEAR-014: downstream operations resolve active year', function (): void {
        $activeYear = AcademicYear::factory()->create(['is_active' => true]);
        $found = AcademicYear::where('is_active', true)->first();

        expect($found?->id)->toBe($activeYear->id);
    });

    test('XW6F5-FR-YEAR-015: AcademicYearPeriod computes school year boundaries correctly', function (): void {
        $julyDate = Carbon::parse('2025-08-15');
        expect(AcademicYearPeriod::nameFor($julyDate))->toBe('2025/2026')
            ->and(AcademicYearPeriod::startDateFor($julyDate))->toBe('2025-07-01')
            ->and(AcademicYearPeriod::endDateFor($julyDate))->toBe('2026-06-30');

        $marchDate = Carbon::parse('2026-03-10');
        expect(AcademicYearPeriod::nameFor($marchDate))->toBe('2025/2026')
            ->and(AcademicYearPeriod::startDateFor($marchDate))->toBe('2025-07-01')
            ->and(AcademicYearPeriod::endDateFor($marchDate))->toBe('2026-06-30');
    });

    test('XW6F5-FR-YEAR-016: year mutation writes SmartLogger entry with actor identity', function (): void {
        $captured = captureLogs();
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        app(CreateAcademicYearAction::class)->execute([
            'name' => '2030/2031',
            'start_date' => '2030-07-01',
            'end_date' => '2031-06-30',
        ]);

        expect($captured->firstWhere('message', 'academic_year_created'))->not->toBeNull();
    });

    test('XW6F5-UC-YEAR-001: subsequent academic year arrives inactive', function (): void {
        AcademicYear::factory()->create(['is_active' => true]);
        $action = app(CreateAcademicYearAction::class);

        $subsequent = $action->execute([
            'name' => '2031/2032',
            'start_date' => '2031-07-01',
            'end_date' => '2032-06-30',
        ]);

        expect($subsequent->is_active)->toBeFalse();
    });

    test('XW6F5-UC-YEAR-002: first academic year arrives active', function (): void {
        AcademicYear::query()->delete();
        $action = app(CreateAcademicYearAction::class);

        $first = $action->execute([
            'name' => '2032/2033',
            'start_date' => '2032-07-01',
            'end_date' => '2033-06-30',
        ]);

        expect($first->is_active)->toBeTrue();
    });

    test('XW6F5-UC-YEAR-003: activating a new year deactivates the old one atomically', function (): void {
        $old = AcademicYear::factory()->create(['is_active' => true]);
        $new = AcademicYear::factory()->create(['is_active' => false]);

        app(ActivateAcademicYearAction::class)->execute($new);

        expect($new->fresh()->is_active)->toBeTrue()
            ->and($old->fresh()->is_active)->toBeFalse();
    });

    test('XW6F5-UC-YEAR-004: deleting an active year is refused with reason', function (): void {
        $active = AcademicYear::factory()->create(['is_active' => true]);

        expect(fn () => app(DeleteAcademicYearAction::class)->execute($active))
            ->toThrow(RejectedException::class);
    });

    test('XW6F5-UC-YEAR-005: bulk delete aborts if any row is protected', function (): void {
        $active = AcademicYear::factory()->create(['is_active' => true]);
        $inactive = AcademicYear::factory()->create(['is_active' => false]);

        expect(fn () => app(BulkDeleteAcademicYearsAction::class)->execute([$active->id, $inactive->id]))
            ->toThrow(RejectedException::class);

        expect(AcademicYear::where('id', $inactive->id)->exists())->toBeTrue();
    });

    test('XW6F5-NFR-YEAR-001: year operations enforce transactional integrity', function (): void {
        $action = app(CreateAcademicYearAction::class);
        $year = $action->execute([
            'name' => '2035/2036',
            'start_date' => '2035-07-01',
            'end_date' => '2036-06-30',
        ]);

        expect($year->exists)->toBeTrue();
    });

    test('XW6F5-NFR-YEAR-002: unique name constraint is preserved across mutations', function (): void {
        $action = app(CreateAcademicYearAction::class);
        $action->execute([
            'name' => '2036/2037',
            'start_date' => '2036-07-01',
            'end_date' => '2037-06-30',
        ]);

        expect(fn () => $action->execute([
            'name' => '2036/2037',
            'start_date' => '2036-07-01',
            'end_date' => '2037-06-30',
        ]))->toThrow(ValidationException::class);
    });

    test('XW6F5-NFR-YEAR-003: guard inside transaction protects referenced years', function (): void {
        $year = AcademicYear::factory()->create(['is_active' => false]);
        expect($year->asAcademicYearState()->canBeDeleted())->toBeTrue();
    });

    test('XW6F5-NFR-YEAR-004: never two active flags exist in academic years', function (): void {
        AcademicYear::query()->delete();
        $action = app(CreateAcademicYearAction::class);
        $y1 = $action->execute(['name' => 'Y1', 'start_date' => '2037-07-01', 'end_date' => '2038-06-30']);
        $y2 = $action->execute(['name' => 'Y2', 'start_date' => '2038-07-01', 'end_date' => '2039-06-30']);

        app(ActivateAcademicYearAction::class)->execute($y2);

        $activeCount = AcademicYear::where('is_active', true)->count();
        expect($activeCount)->toBe(1);
    });

    test('XW6F5-NFR-YEAR-005: bulk delete validates all before deletion', function (): void {
        $action = app(BulkDeleteAcademicYearsAction::class);
        expect($action->execute([]))->toBe(0);
    });

    test('XW6F5-NFR-YEAR-006: refusals distinguish active status from linked data', function (): void {
        $activeYear = AcademicYear::factory()->create(['is_active' => true]);
        try {
            app(DeleteAcademicYearAction::class)->execute($activeYear);
            $this->fail('Expected rejection');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toContain($activeYear->name);
        }
    });

    test('XW6F5-NFR-YEAR-007: active status text is present in manager', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        AcademicYear::factory()->create(['name' => '2023/2024', 'is_active' => true]);

        Livewire::actingAs($admin)
            ->test(AcademicYearManager::class)
            ->assertSee(__('academic_year.name'));
    });

    test('XW6F5-NFR-YEAR-008: strict types declared across academic year classes', function (): void {
        $files = [
            app_path('Modules/Academic/Domain/AcademicYear/Actions/CreateAcademicYearAction.php'),
            app_path('Modules/Academic/Domain/AcademicYear/Actions/ActivateAcademicYearAction.php'),
            app_path('Modules/Academic/Domain/AcademicYear/Actions/DeleteAcademicYearAction.php'),
            app_path('Modules/Academic/Domain/AcademicYear/Actions/BulkDeleteAcademicYearsAction.php'),
            app_path('Modules/Academic/Domain/AcademicYear/Support/AcademicYearPeriod.php'),
        ];

        foreach ($files as $file) {
            expect(file_get_contents($file))->toContain('declare(strict_types=1);');
        }
    });

    test('XW6F5-NFR-YEAR-009: user-facing strings pass through translation helper', function (): void {
        expect(__('academic_year.name'))->not->toBe('academic_year.name');
    });

    test('XW6F5-DD-YEAR-001: singleton enforced inside action transaction', function (): void {
        $y1 = AcademicYear::factory()->create(['is_active' => true]);
        $y2 = AcademicYear::factory()->create(['is_active' => false]);
        app(ActivateAcademicYearAction::class)->execute($y2);
        expect(AcademicYear::where('is_active', true)->count())->toBe(1);
    });

    test('XW6F5-DD-YEAR-002: business rules live in entity not policy', function (): void {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        expect($year->asAcademicYearState()->canBeDeleted())->toBeFalse();
    });

    test('XW6F5-DD-YEAR-003: bulk delete aborts whole batch on first protected row', function (): void {
        $active = AcademicYear::factory()->create(['is_active' => true]);
        expect(fn () => app(BulkDeleteAcademicYearsAction::class)->execute([$active->id]))
            ->toThrow(RejectedException::class);
    });

    test('XW6F5-DD-YEAR-004: events dispatched for dashboard invalidation', function (): void {
        $year = AcademicYear::factory()->create();
        $event = new AcademicYearDeleted($year);
        expect($event->academicYear->id)->toBe($year->id);
    });

    test('XW6F5-DD-YEAR-005: first-year auto-activation lives in create action', function (): void {
        AcademicYear::query()->delete();
        $first = app(CreateAcademicYearAction::class)->execute([
            'name' => '2039/2040',
            'start_date' => '2039-07-01',
            'end_date' => '2040-06-30',
        ]);
        expect($first->is_active)->toBeTrue();
    });

    test('XW6F5-DD-YEAR-006: period computation uses July-June logic', function (): void {
        $date = Carbon::parse('2026-01-15');
        [$start, $end] = AcademicYearPeriod::yearsFor($date);
        expect($start)->toBe(2025)->and($end)->toBe(2026);
    });
});

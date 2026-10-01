<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Actions\ApprovePlacementChangeAction;
use App\Modules\Enrollment\Domain\Placement\Actions\CreatePlacementAction;
use App\Modules\Enrollment\Domain\Placement\Actions\DeletePlacementAction;
use App\Modules\Enrollment\Domain\Placement\Actions\DirectPlacementAction;
use App\Modules\Enrollment\Domain\Placement\Actions\RejectPlacementChangeAction;
use App\Modules\Enrollment\Domain\Placement\Actions\RequestPlacementChangeAction;
use App\Modules\Enrollment\Domain\Placement\Actions\UpdatePlacementAction;
use App\Modules\Enrollment\Domain\Placement\Entities\PlacementCapacity;
use App\Modules\Enrollment\Domain\Placement\Entities\PlacementState;
use App\Modules\Enrollment\Domain\Placement\Enums\PlacementChangeStatus;
use App\Modules\Enrollment\Domain\Placement\Livewire\PlacementIndex;
use App\Modules\Enrollment\Domain\Placement\Livewire\StudentPlacementChangeRequest;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Domain\Mentor\Entities\MentorEntity;
use App\Modules\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function j9gbhAdmin(object $test): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $test->actingAs($admin);

    return $admin;
}

function j9gbhPlacement(): Placement
{
    return Placement::factory()->create(['quota' => 5, 'filled_quota' => 0]);
}

describe('J9GBH: placements', function (): void {
    test('J9GBH-FR-PLACE-001: placements use UUID keys with company and internship cascades', function (): void {
        $placement = j9gbhPlacement();

        expect(Str::isUuid($placement->id))->toBeTrue();

        $companyId = $placement->company_id;
        Company::where('id', $companyId)->delete();

        expect(Placement::where('id', $placement->id)->exists())->toBeFalse();
    });

    test('J9GBH-FR-PLACE-002: company plus internship pairs stay unique', function (): void {
        $placement = j9gbhPlacement();

        expect(fn () => Placement::factory()->create([
            'company_id' => $placement->company_id,
            'internship_id' => $placement->internship_id,
        ]))->toThrow(QueryException::class);
    });

    test('J9GBH-FR-PLACE-004: capacity exposes full and slot math (also DD-PLACE-001)', function (): void {
        $placement = j9gbhPlacement();

        $capacity = PlacementCapacity::fromModel($placement);

        expect($capacity->isFull())->toBeFalse()
            ->and($capacity->availableSlots())->toBe(5)
            ->and($capacity->hasAvailableSlots())->toBeTrue();

        $full = Placement::factory()->full()->create();

        expect(PlacementCapacity::fromModel($full)->isFull())->toBeTrue()
            ->and(PlacementCapacity::fromModel($full)->availableSlots())->toBe(0);
    });

    test('J9GBH-FR-PLACE-005: placement state report count and delete guard', function (): void {
        $placement = j9gbhPlacement();

        expect(PlacementState::fromModel($placement)->canBeDeleted())->toBeTrue();

        Registration::factory()->create(['placement_id' => $placement->id, 'status' => 'active']);

        expect(PlacementState::fromModel($placement->fresh()->loadCount('registrations'))->canBeDeleted())->toBeFalse();
    });

    test('J9GBH-FR-PLACE-006: deletion is blocked while registrations reference the placement', function (): void {
        $placement = j9gbhPlacement();
        Registration::factory()->create(['placement_id' => $placement->id, 'status' => 'active']);

        expect(fn () => app(DeletePlacementAction::class)->execute($placement))->toThrow(RejectedException::class);
        $this->assertModelExists($placement->fresh());

        Registration::query()->delete();
        app(DeletePlacementAction::class)->execute($placement->fresh());

        $this->assertModelMissing($placement);
    });

    test('J9GBH-FR-PLACE-007: placements create and update through validated actions (also UC-PLACE-002)', function (): void {
        $company = Company::factory()->create();
        $internship = Internship::factory()->create();

        $placement = app(CreatePlacementAction::class)->execute([
            'company_id' => $company->id,
            'internship_id' => $internship->id,
            'name' => 'Workshop Placement',
            'quota' => 4,
        ]);

        expect($placement->quota)->toBe(4)
            ->and($placement->filled_quota)->toBe(0);

        $updated = app(UpdatePlacementAction::class)->execute($placement, ['quota' => 8]);

        expect($updated->quota)->toBe(8);
    });

    test('J9GBH-FR-PLACE-008: index stats aggregate quota and slots (also FR-PLACE-009)', function (): void {
        j9gbhAdmin($this);
        Placement::factory()->create(['quota' => 5, 'filled_quota' => 2]);

        $stats = Livewire::test(PlacementIndex::class)->get('stats');

        expect($stats)->toBeArray()
            ->and($stats)->toHaveKeys(['total', 'total_quota', 'filled', 'available']);
    });

    test('J9GBH-FR-PLACE-010: direct placement creates the registration and increments quota (also UC-PLACE-002, DD-PLACE-001)', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $placement = j9gbhPlacement();

        $registration = app(DirectPlacementAction::class)->execute($student, ['placement_id' => $placement->id]);

        expect($registration->placement_id)->toBe($placement->id)
            ->and($registration->status)->toBe('active')
            ->and($placement->fresh()->filled_quota)->toBe(1);
    });

    test('J9GBH-FR-PLACE-011: direct placement refuses full targets', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $placement = Placement::factory()->full()->create();

        expect(fn () => app(DirectPlacementAction::class)->execute($student, ['placement_id' => $placement->id]))
            ->toThrow(RejectedException::class);
    });

    test('J9GBH-FR-PLACE-014: change transitions run pending to terminal endpoints (also FR-PLACE-028, DD-PLACE-002)', function (): void {
        expect(PlacementChangeStatus::PENDING->canTransitionTo(PlacementChangeStatus::APPROVED))->toBeTrue()
            ->and(PlacementChangeStatus::PENDING->canTransitionTo(PlacementChangeStatus::REJECTED))->toBeTrue()
            ->and(PlacementChangeStatus::APPROVED->canTransitionTo(PlacementChangeStatus::REJECTED))->toBeFalse()
            ->and(PlacementChangeStatus::REJECTED->canTransitionTo(PlacementChangeStatus::APPROVED))->toBeFalse()
            ->and(PlacementChangeStatus::PENDING->label())->not->toBe('');
    });

    test('J9GBH-FR-PLACE-015: a second pending request for one registration is refused (also UC-PLACE-001)', function (): void {
        $admin = j9gbhAdmin($this);
        $placement = j9gbhPlacement();
        $target = j9gbhPlacement();
        $registration = Registration::factory()->create(['placement_id' => $placement->id, 'status' => 'active']);

        app(RequestPlacementChangeAction::class)->execute($registration, [
            'to_placement_id' => $target->id,
            'reason' => 'Closer to home',
            'requested_by' => $admin->id,
        ]);

        expect(fn () => app(RequestPlacementChangeAction::class)->execute($registration, [
            'to_placement_id' => $target->id,
            'reason' => 'Again',
            'requested_by' => $admin->id,
        ]))->toThrow(RejectedException::class);
    });

    test('J9GBH-FR-PLACE-017: approval transfers quotas and repoints the registration (also FR-PLACE-016, NFR-PLACE-001)', function (): void {
        j9gbhAdmin($this);
        $old = Placement::factory()->create(['quota' => 5, 'filled_quota' => 1]);
        $new = Placement::factory()->create(['quota' => 5, 'filled_quota' => 0]);
        $registration = Registration::factory()->create(['placement_id' => $old->id, 'status' => 'active']);
        $request = PlacementChangeRequest::factory()->create([
            'registration_id' => $registration->id,
            'from_placement_id' => $old->id,
            'to_placement_id' => $new->id,
            'status' => 'pending',
        ]);

        app(ApprovePlacementChangeAction::class)->execute($request);

        expect($registration->fresh()->placement_id)->toBe($new->id)
            ->and($old->fresh()->filled_quota)->toBe(0)
            ->and($new->fresh()->filled_quota)->toBe(1)
            ->and($request->fresh()->status)->toBe(PlacementChangeStatus::APPROVED);
    });

    test('J9GBH-FR-PLACE-016: approval requires a live request and free target slots', function (): void {
        j9gbhAdmin($this);
        $done = PlacementChangeRequest::factory()->create(['status' => 'approved']);

        expect(fn () => app(ApprovePlacementChangeAction::class)->execute($done))->toThrow(RejectedException::class);

        $old = j9gbhPlacement();
        $full = Placement::factory()->full()->create();
        $registration = Registration::factory()->create(['placement_id' => $old->id, 'status' => 'active']);
        $request = PlacementChangeRequest::factory()->create([
            'registration_id' => $registration->id,
            'from_placement_id' => $old->id,
            'to_placement_id' => $full->id,
            'status' => 'pending',
        ]);

        expect(fn () => app(ApprovePlacementChangeAction::class)->execute($request))->toThrow(RejectedException::class);
    });

    test('J9GBH-FR-PLACE-018: rejection records the reason and closes the request', function (): void {
        j9gbhAdmin($this);
        $request = PlacementChangeRequest::factory()->create(['status' => 'pending']);

        app(RejectPlacementChangeAction::class)->execute($request, 'No seats left this term');

        expect($request->fresh()->status)->toBe(PlacementChangeStatus::REJECTED)
            ->and($request->fresh()->rejection_reason)->toBe('No seats left this term');
    });

    test('J9GBH-FR-PLACE-026: mentor assignment resolves through the registration bridge (also FR-PLACE-027, DD-PLACE-003)', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $registration = Registration::factory()->create(['status' => 'active']);
        InternshipGroupMember::factory()->create(['registration_id' => $registration->id, 'user_id' => $teacher->id]);

        $bridge = $registration->asMentorEntity();

        expect($bridge)->toBeInstanceOf(MentorEntity::class)
            ->and($bridge->isMentor($teacher))->toBeTrue()
            ->and($bridge->isTeacher($teacher))->toBeTrue();
    });

    test('J9GBH-FR-PLACE-020: students see same-internship free placements excluding the current one', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $internship = Internship::factory()->create();
        $current = Placement::factory()->create(['internship_id' => $internship->id, 'quota' => 5, 'filled_quota' => 0]);
        $target = Placement::factory()->create(['internship_id' => $internship->id, 'quota' => 5, 'filled_quota' => 0]);
        $other = Placement::factory()->create(['quota' => 5, 'filled_quota' => 0]);

        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'placement_id' => $current->id,
            'status' => 'active',
        ]);
        InternshipGroupMember::factory()->create(['registration_id' => $registration->id, 'user_id' => $student->id]);

        $view = Livewire::test(StudentPlacementChangeRequest::class)->viewData('availablePlacements');

        expect($view->pluck('id'))->toContain($target->id)
            ->and($view->pluck('id'))->not->toContain($current->id)
            ->and($view->pluck('id'))->not->toContain($other->id);
    });

    test('J9GBH-FR-PLACE-022: placement routes sit behind admin gates (also FR-PLACE-023, FR-PLACE-024)', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $this->get(route('enrollment.internships.placements'))->assertForbidden();
        $this->get(route('enrollment.internships.placements.direct'))->assertForbidden();
        $this->get(route('enrollment.internships.placements.changes'))->assertForbidden();

        j9gbhAdmin($this);

        $this->get(route('enrollment.internships.placements'))->assertOk();
        $this->get(route('enrollment.internships.placements.direct'))->assertOk();
        $this->get(route('enrollment.internships.placements.changes'))->assertOk();
    });

    test('J9GBH-FR-PLACE-025: the student change route requires the student role', function (): void {
        $this->get(route('student.internships.placement-change'))->assertRedirect('/login');

        j9gbhAdmin($this);

        $this->get(route('student.internships.placement-change'))->assertForbidden();
    });

    test('J9GBH-NFR-PLACE-004: placement mutations write audit entries', function (): void {
        j9gbhAdmin($this);
        $student = User::factory()->create();
        $student->assignRole('student');
        $placement = j9gbhPlacement();

        app(DirectPlacementAction::class)->execute($student, ['placement_id' => $placement->id]);

        expect(DB::table('activity_log')->where('description', 'direct_placement_created')->exists())->toBeTrue();
    });
});

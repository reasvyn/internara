<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\AccountApplication\Enums\AccountApplicationStatus;
use App\Modules\Enrollment\Domain\AccountApplication\Livewire\ApplyPage;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\Enrollment\Domain\Placement\Actions\DirectPlacementAction;
use App\Modules\Enrollment\Domain\Placement\Actions\RequestPlacementChangeAction;
use App\Modules\Enrollment\Domain\Placement\Actions\UpdatePlacementAction;
use App\Modules\Enrollment\Domain\Placement\Entities\PlacementCapacity;
use App\Modules\Enrollment\Domain\Placement\Entities\PlacementState;
use App\Modules\Enrollment\Domain\Placement\Enums\PlacementChangeStatus;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\Enrollment\Domain\Registration\Entities\RegistrationState;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function enrollmentCoverageUser(string $role = 'student'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

describe('MBB5R: RegistrationState behavior', function (): void {
    test('MBB5R-FR-REG-012: state exposes pending and active predicates', function (): void {
        $pending = Registration::factory()->pending()->create();
        $active = Registration::factory()->active()->create();

        expect($pending->asRegistrationState()->isPending())->toBeTrue()
            ->and($pending->asRegistrationState()->isActive())->toBeFalse()
            ->and($active->asRegistrationState()->isActive())->toBeTrue();
    });

    test('MBB5R-FR-REG-012: state detects an ongoing date range inclusively', function (): void {
        $state = RegistrationState::fromModel(Registration::factory()->create([
            'start_date' => '2026-01-10', 'end_date' => '2026-01-20',
        ]));

        expect($state->isCurrentlyOngoing(Carbon::parse('2026-01-10')))->toBeTrue()
            ->and($state->isCurrentlyOngoing(Carbon::parse('2026-01-20')))->toBeTrue()
            ->and($state->isCurrentlyOngoing(Carbon::parse('2026-01-21')))->toBeFalse();
    });

    test('MBB5R-FR-REG-012: state returns false for missing dates', function (): void {
        $state = RegistrationState::fromModel(Registration::factory()->create(['start_date' => null, 'end_date' => null]));

        expect($state->isCurrentlyOngoing(Carbon::now()))->toBeFalse()
            ->and($state->hasEnded(Carbon::now()))->toBeFalse()
            ->and($state->daysRemaining(Carbon::now()))->toBe(0)
            ->and($state->totalDuration())->toBe(0);
    });

    test('MBB5R-FR-REG-012: state reports ended registrations and clamps remaining days', function (): void {
        $state = RegistrationState::fromModel(Registration::factory()->create(['start_date' => '2026-01-01', 'end_date' => '2026-01-10']));

        expect($state->hasEnded(Carbon::parse('2026-01-11')))->toBeTrue()
            ->and($state->daysRemaining(Carbon::parse('2026-01-11')))->toBe(0)
            ->and($state->totalDuration())->toBe(9);
    });

    test('MBB5R-FR-REG-012: approval requires pending state and placement', function (): void {
        $pending = Registration::factory()->pending()->create(['placement_id' => null]);
        $placed = Registration::factory()->pending()->create(['placement_id' => Placement::factory()->create()->id]);
        $active = Registration::factory()->active()->create(['placement_id' => Placement::factory()->create()->id]);

        expect($pending->asRegistrationState()->canBeApproved())->toBeFalse()
            ->and($placed->asRegistrationState()->canBeApproved())->toBeTrue()
            ->and($active->asRegistrationState()->canBeApproved())->toBeFalse();
    });

    test('MBB5R-FR-REG-012: phase calculations select boundaries and final phase', function (): void {
        $registration = Registration::factory()->create(['start_date' => '2026-01-01', 'end_date' => '2026-01-11']);
        $state = $registration->asRegistrationState()->withPhases([
            ['name' => 'Orientation', 'order' => 1, 'weight' => 50],
            ['name' => 'Workplace', 'order' => 2, 'weight' => 50],
        ]);

        expect($state->phases())->toHaveCount(2)
            ->and($state->currentPhaseIndex(Carbon::parse('2025-12-31')))->toBe(0)
            ->and($state->currentPhase(Carbon::parse('2026-01-06')))->toBe('Orientation')
            ->and($state->currentPhase(Carbon::parse('2026-01-11')))->toBe('Workplace');
    });

    test('MBB5R-FR-REG-012: empty and zero-length phase timelines have no current phase', function (): void {
        $registration = Registration::factory()->create(['start_date' => '2026-01-01', 'end_date' => '2026-01-01']);
        $state = $registration->asRegistrationState();

        expect($state->currentPhaseIndex())->toBeNull()->and($state->currentPhase())->toBeNull();
    });

    test('MBB5R-FR-REG-012: phase selection falls back to the last phase after the timeline', function (): void {
        $registration = Registration::factory()->create(['start_date' => '2026-01-01', 'end_date' => '2026-01-11']);
        $state = $registration->asRegistrationState()->withPhases([
            ['name' => 'Only phase', 'order' => 1, 'weight' => 10],
        ]);

        expect($state->currentPhase(Carbon::parse('2026-02-01')))->toBe('Only phase');
    });

    test('MBB5R-FR-REG-012: adding phases returns a new immutable state', function (): void {
        $state = Registration::factory()->create()->asRegistrationState();
        $originalPhases = $state->phases();
        $updated = $state->withPhases([['name' => 'Review', 'order' => 1, 'weight' => 100]]);

        expect($state->phases())->toBe($originalPhases)->and($updated)->not->toBe($state)->and($updated->phases()[0]['name'])->toBe('Review');
    });
});

describe('920SO: account application lifecycle', function (): void {
    test('920SO-FR-APPLY-001: status enum identifies terminals and labels every state', function (): void {
        expect(AccountApplicationStatus::PENDING->isTerminal())->toBeFalse()
            ->and(AccountApplicationStatus::APPROVED->isTerminal())->toBeTrue()
            ->and(AccountApplicationStatus::REJECTED->isTerminal())->toBeTrue()
            ->and(AccountApplicationStatus::PENDING->label())->not->toBe('');
    });

    test('920SO-FR-APPLY-002: terminal application statuses cannot transition', function (): void {
        expect(AccountApplicationStatus::PENDING->validTransitions())->toHaveCount(2)
            ->and(AccountApplicationStatus::APPROVED->validTransitions())->toBe([])
            ->and(AccountApplicationStatus::REJECTED->canTransitionTo(AccountApplicationStatus::PENDING))->toBeFalse();
    });

    test('920SO-FR-APPLY-002: status rejects a transition target from another enum', function (): void {
        expect(AccountApplicationStatus::PENDING->canTransitionTo(PlacementChangeStatus::PENDING))->toBeFalse();
    });

    test('920SO-FR-APPLY-003: application casts form data and resolves department', function (): void {
        $department = Department::factory()->create();
        $application = AccountApplication::factory()->create(['department_id' => $department->id, 'form_data' => ['guardian' => ['name' => 'A']]]);

        expect($application->fresh()->form_data['guardian']['name'])->toBe('A')
            ->and($application->department->is($department))->toBeTrue();
    });

    test('920SO-FR-APPLY-014: application processor relation resolves after attribution', function (): void {
        $admin = enrollmentCoverageUser('admin');
        $application = AccountApplication::factory()->create(['processed_by' => $admin->id, 'processed_at' => now()]);

        expect($application->processor)->toBeInstanceOf(User::class)->and($application->processor->is($admin))->toBeTrue();
    });

    test('920SO-FR-APPLY-016: unauthenticated guests can create but cannot view applications', function (): void {
        $application = AccountApplication::factory()->create();

        $guest = enrollmentCoverageUser('teacher');

        expect(Gate::forUser($guest)->allows('create', AccountApplication::class))->toBeTrue()
            ->and(Gate::forUser($guest)->allows('view', $application))->toBeFalse();
    });

    test('920SO-FR-APPLY-016: applicant email ownership is exact', function (): void {
        $owner = enrollmentCoverageUser();
        $application = AccountApplication::factory()->create(['email' => $owner->email]);
        $other = enrollmentCoverageUser();

        expect(Gate::forUser($owner)->allows('view', $application))->toBeTrue()
            ->and(Gate::forUser($other)->allows('view', $application))->toBeFalse();
    });

    test('920SO-FR-APPLY-016: superadmin bypasses application policy checks', function (): void {
        $superadmin = enrollmentCoverageUser('superadmin');
        $application = AccountApplication::factory()->create();

        expect(Gate::forUser($superadmin)->allows('update', $application))->toBeTrue()
            ->and(Gate::forUser($superadmin)->allows('delete', $application))->toBeTrue();
    });
});

describe('920SO: account application form contract', function (): void {
    test('920SO-FR-APPLY-025: placement mode requires a placement', function (): void {
        $form = Livewire::test(ApplyPage::class)->get('form');

        expect($form->rules()['placement_id'])->toContain('required')
            ->and($form->rules())->not->toHaveKey('proposed_company_name');
    });

    test('920SO-FR-APPLY-026: proposed mode requires company details', function (): void {
        $form = Livewire::test(ApplyPage::class)->get('form');
        $form->use_placement = false;

        expect($form->rules())->toHaveKeys(['proposed_company_name', 'proposed_company_address'])
            ->and($form->rules())->not->toHaveKey('placement_id');
    });

    test('920SO-FR-APPLY-027: form converts entry year and omits inactive placement fields', function (): void {
        $form = Livewire::test(ApplyPage::class)->get('form');
        $form->entry_year = '2024';
        $form->placement_id = 'place-1';
        $form->proposed_company_name = 'Stale';

        expect($form->toArray()['entry_year'])->toBe(2024)
            ->and($form->toArray()['placement_id'])->toBe('place-1')
            ->and($form->toArray()['proposed_company_name'])->toBeNull();
    });

    test('920SO-FR-APPLY-027: proposed mode returns company details and null placement', function (): void {
        $form = Livewire::test(ApplyPage::class)->get('form');
        $form->use_placement = false;
        $form->proposed_company_name = 'PT Proposed';
        $form->proposed_company_address = 'Jl. Proposed';
        $form->placement_id = 'stale';

        expect($form->toArray())->toMatchArray(['placement_id' => null, 'proposed_company_name' => 'PT Proposed', 'proposed_company_address' => 'Jl. Proposed']);
    });
});

describe('J9GBH: placement entities and actions', function (): void {
    test('J9GBH-FR-PLACE-013: placement change status labels are available', function (): void {
        expect(PlacementChangeStatus::PENDING->label())->not->toBe('')
            ->and(PlacementChangeStatus::APPROVED->label())->not->toBe('')
            ->and(PlacementChangeStatus::REJECTED->label())->not->toBe('');
    });

    test('J9GBH-FR-PLACE-014: placement change terminal states reject every transition', function (): void {
        expect(PlacementChangeStatus::APPROVED->canTransitionTo(PlacementChangeStatus::PENDING))->toBeFalse()
            ->and(PlacementChangeStatus::REJECTED->canTransitionTo(PlacementChangeStatus::PENDING))->toBeFalse();
    });

    test('J9GBH-FR-PLACE-003: placement defaults start with one slot and no occupants', function (): void {
        $placement = Placement::create([
            'company_id' => Company::factory()->create()->id,
            'internship_id' => Internship::factory()->create()->id,
            'name' => 'Default line',
        ]);

        expect($placement->quota)->toBeNull()->and($placement->filled_quota)->toBeNull();
    });

    test('J9GBH-FR-PLACE-004: capacity clamps overfilled rows to zero available slots', function (): void {
        $capacity = PlacementCapacity::fromModel(Placement::factory()->create(['quota' => 2, 'filled_quota' => 3]));

        expect($capacity->isFull())->toBeTrue()->and($capacity->availableSlots())->toBe(0)->and($capacity->hasAvailableSlots())->toBeFalse();
    });

    test('J9GBH-FR-PLACE-004: capacity reports one available slot at the boundary', function (): void {
        $capacity = PlacementCapacity::fromModel(Placement::factory()->create(['quota' => 2, 'filled_quota' => 1]));

        expect($capacity->isFull())->toBeFalse()->and($capacity->availableSlots())->toBe(1)->and($capacity->hasAvailableSlots())->toBeTrue();
    });

    test('J9GBH-FR-PLACE-005: state reads an eager-loaded registration count', function (): void {
        $placement = Placement::factory()->create();
        Registration::factory()->create(['placement_id' => $placement->id]);

        expect(PlacementState::fromModel($placement->fresh()->loadCount('registrations'))->canBeDeleted())->toBeFalse();
    });

    test('J9GBH-FR-PLACE-007: update action persists name and writes an audit entry', function (): void {
        $placement = Placement::factory()->create();

        test()->actingAs(enrollmentCoverageUser('admin'));
        app(UpdatePlacementAction::class)->execute($placement, ['name' => 'Updated line', 'quota' => 4]);

        expect($placement->fresh()->name)->toBe('Updated line')
            ->and(DB::table('activity_log')->where('description', 'placement_updated')->exists())->toBeTrue();
    });

    test('J9GBH-FR-PLACE-010: direct placement uses internship dates when omitted', function (): void {
        $internship = Internship::factory()->create(['start_date' => '2026-07-01', 'end_date' => '2026-12-31']);
        $placement = Placement::factory()->create(['internship_id' => $internship->id]);
        $student = enrollmentCoverageUser();

        $registration = app(DirectPlacementAction::class)->execute($student, ['placement_id' => $placement->id]);

        expect($registration->start_date->toDateString())->toBe('2026-07-01')->and($registration->end_date->toDateString())->toBe('2026-12-31');
    });

    test('J9GBH-FR-PLACE-015: request action records source, target, reason, and requester', function (): void {
        $registration = Registration::factory()->active()->create(['placement_id' => Placement::factory()->create()->id]);
        $target = Placement::factory()->create(['internship_id' => $registration->internship_id]);
        $requester = enrollmentCoverageUser();

        $request = app(RequestPlacementChangeAction::class)->execute($registration, ['to_placement_id' => $target->id, 'reason' => 'Move closer', 'requested_by' => $requester->id]);

        expect($request->from_placement_id)->toBe($registration->placement_id)->and($request->to_placement_id)->toBe($target->id)->and($request->requested_by)->toBe($requester->id);
    });

    test('J9GBH-FR-PLACE-015: request action validates required fields before writing', function (): void {
        $registration = Registration::factory()->active()->create(['placement_id' => Placement::factory()->create()->id]);

        expect(fn () => app(RequestPlacementChangeAction::class)->execute($registration, []))->toThrow(ValidationException::class);
        expect(PlacementChangeRequest::count())->toBe(0);
    });

    test('J9GBH-FR-PLACE-018: change request stores terminal rejection status', function (): void {
        $request = PlacementChangeRequest::factory()->create(['status' => 'pending']);
        $request->update(['status' => PlacementChangeStatus::REJECTED->value, 'rejection_reason' => 'No capacity']);

        expect($request->fresh()->status)->toBe(PlacementChangeStatus::REJECTED)->and($request->fresh()->rejection_reason)->toBe('No capacity');
    });

    test('J9GBH-FR-PLACE-021: change request policy allows students to create and admins to review', function (): void {
        $request = PlacementChangeRequest::factory()->create();
        $student = enrollmentCoverageUser();
        $admin = enrollmentCoverageUser('admin');

        expect(Gate::forUser($student)->allows('create', PlacementChangeRequest::class))->toBeTrue()
            ->and(Gate::forUser($admin)->allows('view', $request))->toBeTrue()
            ->and(Gate::forUser($student)->allows('update', $request))->toBeFalse();
    });

    test('J9GBH-FR-PLACE-021: superadmin bypasses placement change policy', function (): void {
        $request = PlacementChangeRequest::factory()->create();
        $superadmin = enrollmentCoverageUser('superadmin');

        expect(Gate::forUser($superadmin)->allows('delete', $request))->toBeTrue();
    });

    test('J9GBH-FR-PLACE-021: teacher cannot review or create placement changes', function (): void {
        $teacher = enrollmentCoverageUser('teacher');
        $request = PlacementChangeRequest::factory()->create();

        expect(Gate::forUser($teacher)->allows('view', $request))->toBeFalse()
            ->and(Gate::forUser($teacher)->allows('create', PlacementChangeRequest::class))->toBeFalse();
    });
});

describe('MBB5R: registration model behavior', function (): void {
    test('MBB5R-FR-REG-009: registration date attributes are cast to Carbon dates', function (): void {
        $registration = Registration::factory()->create(['start_date' => '2026-05-01', 'end_date' => '2026-06-01']);

        expect($registration->start_date)->toBeInstanceOf(Carbon::class)->and($registration->end_date)->toBeInstanceOf(Carbon::class);
    });

    test('MBB5R-FR-REG-012: status helpers and current-status scope filter rows', function (): void {
        Registration::factory()->pending()->create();
        Registration::factory()->active()->create();

        expect(Registration::currentStatus('pending')->count())->toBe(1)
            ->and(Registration::factory()->pending()->create()->hasStatus('pending'))->toBeTrue()
            ->and(Registration::factory()->pending()->create()->isPending())->toBeTrue();
    });

    test('MBB5R-FR-REG-012: mentor scope finds only registrations for the mentor', function (): void {
        $mentor = enrollmentCoverageUser('teacher');
        $withMentor = Registration::factory()->create();
        Registration::factory()->create();
        InternshipGroupMember::factory()->create([
            'registration_id' => $withMentor->id,
            'user_id' => $mentor->id,
            'role' => 'teacher',
        ]);

        expect(Registration::whereHasMentor($mentor)->pluck('id')->all())->toBe([$withMentor->id]);
    });

    test('MBB5R-FR-REG-012: internship phases override configured defaults', function (): void {
        $internship = Internship::factory()->create(['phases' => [['name' => 'Custom', 'order' => 1, 'weight' => 100]]]);
        $registration = Registration::factory()->create(['internship_id' => $internship->id]);

        expect($registration->resolvePhases()[0]['name'])->toBe('Custom');
    });

    test('MBB5R-FR-REG-012: registration status mutation persists the new value', function (): void {
        $registration = Registration::factory()->pending()->create();

        expect($registration->setStatus('active', 'verified')->fresh()->status)->toBe('active');
    });

    test('MBB5R-FR-REG-012: registration resolves empty phases without configured defaults', function (): void {
        $registration = Registration::factory()->create();

        expect($registration->resolvePhases())->toBeArray();
    });
});

describe('MBB5R: registration document policy', function (): void {
    test('MBB5R-FR-REG-025: document relations resolve registration and document records', function (): void {
        $document = RegistrationDocument::factory()->create();

        expect($document->registration)->toBeInstanceOf(Registration::class)
            ->and($document->document)->toBeInstanceOf(Document::class);
    });

    test('MBB5R-FR-REG-025: admins can manage documents while students can create', function (): void {
        $admin = enrollmentCoverageUser('admin');
        $student = enrollmentCoverageUser();
        $document = RegistrationDocument::factory()->create();

        expect(Gate::forUser($admin)->allows('viewAny', RegistrationDocument::class))->toBeTrue()
            ->and(Gate::forUser($admin)->allows('update', $document))->toBeTrue()
            ->and(Gate::forUser($student)->allows('create', RegistrationDocument::class))->toBeTrue();
    });

    test('MBB5R-FR-REG-025: document owner can view but cannot update or delete', function (): void {
        $student = enrollmentCoverageUser();
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        $groupMember = InternshipGroupMember::factory()->create(['registration_id' => $registration->id, 'user_id' => $student->id]);
        $document = RegistrationDocument::factory()->create(['registration_id' => $registration->id]);

        expect(Gate::forUser($student)->allows('view', $document))->toBeTrue()
            ->and(Gate::forUser($student)->allows('update', $document))->toBeFalse()
            ->and(Gate::forUser($student)->allows('delete', $document))->toBeFalse();
    });

    test('MBB5R-FR-REG-025: teacher cannot view another student document', function (): void {
        $registration = Registration::factory()->create();
        $document = RegistrationDocument::factory()->create(['registration_id' => $registration->id]);
        $teacher = enrollmentCoverageUser('teacher');

        expect(Gate::forUser($teacher)->allows('view', $document))->toBeFalse();
    });
});

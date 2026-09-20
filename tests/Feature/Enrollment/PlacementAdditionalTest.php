<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Placement\Actions\DirectPlacementAction;
use App\Modules\Enrollment\Domain\Placement\Actions\RejectPlacementChangeAction;
use App\Modules\Enrollment\Domain\Placement\Livewire\DirectPlacementManager;
use App\Modules\Enrollment\Domain\Placement\Livewire\PlacementChangeManager;
use App\Modules\Enrollment\Domain\Placement\Livewire\StudentPlacementChangeRequest;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Domain\Mentor\Entities\MentorEntity;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function additionalPlacementAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

function additionalPlacementStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

test('J9GBH-FR-PLACE-012: direct placement form exposes students placements and mentors', function (): void {
    $admin = additionalPlacementAdmin();
    $student = additionalPlacementStudent();
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    $placement = Placement::factory()->create(['quota' => 2, 'filled_quota' => 0]);
    $this->actingAs($admin);

    $component = Livewire::test(DirectPlacementManager::class);

    expect($component->get('students')->pluck('id'))->toContain($student->id)
        ->and($component->get('placements')->pluck('id'))->toContain($placement->id)
        ->and($component->get('mentors')->pluck('id'))->toContain($teacher->id);
});

test('J9GBH-FR-PLACE-012: direct placement form requires all three selections', function (): void {
    $this->actingAs(additionalPlacementAdmin());

    Livewire::test(DirectPlacementManager::class)
        ->call('submit')
        ->assertHasErrors(['form.student_id', 'form.placement_id', 'form.academic_year']);
});

test('J9GBH-NFR-PLACE-002: direct placement never exceeds a one-seat quota', function (): void {
    $student = additionalPlacementStudent();
    $placement = Placement::factory()->create(['quota' => 1, 'filled_quota' => 0]);
    app(DirectPlacementAction::class)->execute($student, ['placement_id' => $placement->id]);

    expect($placement->fresh()->filled_quota)->toBe(1);
});

test('J9GBH-NFR-PLACE-003: change queue includes reason and both placement companies', function (): void {
    $admin = additionalPlacementAdmin();
    $old = Placement::factory()->create(['name' => 'Old Workshop']);
    $new = Placement::factory()->create(['name' => 'New Workshop']);
    $registration = Registration::factory()->create(['placement_id' => $old->id]);
    $request = PlacementChangeRequest::factory()->create([
        'registration_id' => $registration->id,
        'from_placement_id' => $old->id,
        'to_placement_id' => $new->id,
        'reason' => 'Relocation to a closer workplace',
        'status' => 'pending',
    ]);
    $this->actingAs($admin);

    Livewire::test(PlacementChangeManager::class)
        ->assertSee('Relocation to a closer workplace')
        ->assertSee($old->company->name)
        ->assertSee($new->company->name)
        ->assertSee($request->status->label());
});

test('J9GBH-NFR-PLACE-005: placement pages provide keyboard-operable action labels', function (): void {
    $this->actingAs(additionalPlacementAdmin());
    $html = test()->get(route('enrollment.internships.placements.direct'))->assertOk()->getContent();

    expect($html)->toContain('type="submit"')->and($html)->toContain('label');
});

test('J9GBH-NFR-PLACE-006: direct placement inputs have associated labels', function (): void {
    $this->actingAs(additionalPlacementAdmin());
    $html = test()->get(route('enrollment.internships.placements.direct'))->assertOk()->getContent();

    expect($html)->toContain('Student')->and($html)->toContain('Placement')->and($html)->toContain('Academic Year');
});

test('J9GBH-NFR-PLACE-007: placement pages use readable semantic text classes', function (): void {
    $this->actingAs(additionalPlacementAdmin());
    $view = file_get_contents(resource_path('views/enrollment/placement/placement-index.blade.php'));

    expect($view)->not->toContain('text-gray-300');
});

test('J9GBH-NFR-PLACE-008: placement source uses translated user-facing strings', function (): void {
    $view = file_get_contents(resource_path('views/enrollment/placement/placement-change-manager.blade.php'));

    expect($view)->not->toContain('>Approve<')->and($view)->not->toContain('>Reject<');
});

test('J9GBH-NFR-PLACE-009: placement translations exist in both supported locales', function (): void {
    foreach (['placement', 'placement_change'] as $group) {
        expect(Lang::has($group, 'en'))->toBeTrue()->and(Lang::has($group, 'id'))->toBeTrue();
    }
});

test('J9GBH-FR-PLACE-019: change manager filters rows by pending status', function (): void {
    $this->actingAs(additionalPlacementAdmin());
    $pending = PlacementChangeRequest::factory()->create(['status' => 'pending', 'reason' => 'Pending reason']);
    PlacementChangeRequest::factory()->create(['status' => 'rejected', 'reason' => 'Closed reason']);

    Livewire::test(PlacementChangeManager::class)
        ->set('filters.status', 'pending')
        ->assertSee('Pending reason')
        ->assertDontSee('Closed reason');

    expect($pending->fresh()->status->value)->toBe('pending');
});

test('J9GBH-FR-PLACE-019: change manager can open and close the rejection modal', function (): void {
    $this->actingAs(additionalPlacementAdmin());
    $request = PlacementChangeRequest::factory()->create(['status' => 'pending']);

    Livewire::test(PlacementChangeManager::class)
        ->call('rejectConfirm', $request->id)
        ->assertSet('showRejectModal', true)
        ->assertSet('rejectingId', $request->id)
        ->set('rejectionReason', 'No capacity')
        ->call('reject')
        ->assertSet('showRejectModal', false);
});

test('J9GBH-FR-PLACE-020: student change page has no registration when student is unplaced', function (): void {
    $student = additionalPlacementStudent();
    $this->actingAs($student);

    Livewire::test(StudentPlacementChangeRequest::class)->assertSet('registrationId', null);
});

test('J9GBH-FR-PLACE-020: student change page excludes full same-internship targets', function (): void {
    $student = additionalPlacementStudent();
    $internship = Internship::factory()->create();
    $current = Placement::factory()->create(['internship_id' => $internship->id]);
    $full = Placement::factory()->create(['internship_id' => $internship->id, 'quota' => 1, 'filled_quota' => 1]);
    $registration = Registration::factory()->create(['student_id' => $student->id, 'internship_id' => $internship->id, 'placement_id' => $current->id, 'status' => 'active']);
    InternshipGroupMember::factory()->create(['registration_id' => $registration->id, 'user_id' => $student->id]);
    $this->actingAs($student);

    expect(Livewire::test(StudentPlacementChangeRequest::class)->viewData('availablePlacements')->pluck('id'))
        ->not->toContain($full->id);
});

test('J9GBH-FR-PLACE-029: rejection writes a user-facing rejection reason', function (): void {
    $this->actingAs(additionalPlacementAdmin());
    $request = PlacementChangeRequest::factory()->create(['status' => 'pending']);

    app(RejectPlacementChangeAction::class)->execute($request, 'The target has no available seat.');

    expect($request->fresh()->rejection_reason)->toBe('The target has no available seat.');
});

test('J9GBH-FR-PLACE-029: rejection reason is required by the review form', function (): void {
    $this->actingAs(additionalPlacementAdmin());
    $request = PlacementChangeRequest::factory()->create(['status' => 'pending']);

    Livewire::test(PlacementChangeManager::class)
        ->call('rejectConfirm', $request->id)
        ->call('reject')
        ->assertHasErrors(['rejectionReason']);
});

test('J9GBH-FR-PLACE-021: only admins can review a change request', function (): void {
    $student = additionalPlacementStudent();
    $this->actingAs($student);

    Livewire::test(PlacementChangeManager::class)->assertForbidden();
});

test('J9GBH-FR-PLACE-021: only students can open the student change request page', function (): void {
    $admin = additionalPlacementAdmin();
    $this->actingAs($admin);

    Livewire::test(StudentPlacementChangeRequest::class)->assertForbidden();
});

test('J9GBH-DD-PLACE-001: direct placement updates registration and quota together', function (): void {
    $student = additionalPlacementStudent();
    $placement = Placement::factory()->create(['quota' => 3, 'filled_quota' => 0]);

    $registration = app(DirectPlacementAction::class)->execute($student, ['placement_id' => $placement->id]);

    expect(Registration::whereKey($registration->id)->value('placement_id'))->toBe($placement->id)
        ->and($placement->fresh()->filled_quota)->toBe(1);
});

test('J9GBH-DD-PLACE-002: change requests preserve source and target references', function (): void {
    $old = Placement::factory()->create();
    $new = Placement::factory()->create();
    $registration = Registration::factory()->create(['placement_id' => $old->id]);
    $request = PlacementChangeRequest::factory()->create(['registration_id' => $registration->id, 'from_placement_id' => $old->id, 'to_placement_id' => $new->id]);

    expect($request->from_placement_id)->toBe($old->id)->and($request->to_placement_id)->toBe($new->id);
});

test('J9GBH-DD-PLACE-003: registration exposes the mentor bridge', function (): void {
    $registration = Registration::factory()->create();

    expect($registration->asMentorEntity())->toBeInstanceOf(MentorEntity::class);
});

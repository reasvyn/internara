<?php

declare(strict_types=1);

use App\Actions\Internship\VerifyRegistrationAction;
use App\Livewire\Internship\RegistrationVerification;
use App\Models\Internship;
use App\Models\Mentee;
use App\Models\Mentor;
use App\Models\Placement;
use App\Models\Registration;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'teacher', 'student', 'supervisor'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->admin = User::factory()->create()->assignRole('admin');
    $this->student = User::factory()->create()->assignRole('student');

    $this->internship = Internship::factory()->create([
        'status' => 'published',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonths(5),
    ]);

    $this->placement = Placement::factory()->create([
        'internship_id' => $this->internship->id,
        'quota' => 10,
        'filled_quota' => 0,
    ]);
});

// ─── Action Tests ───────────────────────────────────────────────────

it('verifies a pending registration and assigns placement', function () {
    $registration = Registration::factory()->create([
        'internship_id' => $this->internship->id,
        'status' => 'pending',
    ]);
    $registration->setStatus('pending', 'Self-registered');

    $action = app(VerifyRegistrationAction::class);
    $result = $action->execute($registration->id, [
        'placement_id' => $this->placement->id,
    ]);

    $result->refresh();

    expect($result->placement_id)->toBe($this->placement->id);
    expect($result->hasStatus('active'))->toBeTrue();
    expect($this->placement->fresh()->filled_quota)->toBe(1);
});

it('verifies a pending registration and assigns mentors', function () {
    $mentor = Mentor::factory()->create(['type' => 'school_teacher']);

    $registration = Registration::factory()->create([
        'internship_id' => $this->internship->id,
        'status' => 'pending',
    ]);
    $registration->setStatus('pending', 'Self-registered');

    $action = app(VerifyRegistrationAction::class);
    $result = $action->execute($registration->id, [
        'placement_id' => $this->placement->id,
        'mentor_ids' => [$mentor->id],
    ]);

    expect($result->mentors()->count())->toBe(1);
    expect($result->mentors()->first()->id)->toBe($mentor->id);
});

it('throws exception if registration is not pending', function () {
    $registration = Registration::factory()->create([
        'internship_id' => $this->internship->id,
        'status' => 'active',
    ]);
    $registration->setStatus('active', 'Already active');

    $action = app(VerifyRegistrationAction::class);

    expect(fn () => $action->execute($registration->id, [
        'placement_id' => $this->placement->id,
    ]))->toThrow(RuntimeException::class, 'not in pending status');
});

it('throws exception if placement is full', function () {
    $fullPlacement = Placement::factory()->create([
        'internship_id' => $this->internship->id,
        'quota' => 1,
        'filled_quota' => 1,
    ]);

    $registration = Registration::factory()->create([
        'internship_id' => $this->internship->id,
        'status' => 'pending',
    ]);
    $registration->setStatus('pending', 'Self-registered');

    $action = app(VerifyRegistrationAction::class);

    expect(fn () => $action->execute($registration->id, [
        'placement_id' => $fullPlacement->id,
    ]))->toThrow(RuntimeException::class, 'quota is already full');
});

// ─── Livewire Component Tests ───────────────────────────────────────

it('admin can view pending registrations', function () {
    $mentee = Mentee::factory()->create(['user_id' => $this->student->id]);
    $registration = Registration::factory()->create([
        'mentee_id' => $mentee->id,
        'internship_id' => $this->internship->id,
        'status' => 'pending',
    ]);
    $registration->setStatus('pending', 'test');

    Livewire::actingAs($this->admin)
        ->test(RegistrationVerification::class)
        ->assertSee($this->student->name);
});

it('shows empty state when no pending registrations', function () {
    Livewire::actingAs($this->admin)
        ->test(RegistrationVerification::class)
        ->assertSee('No pending registrations');
});

it('cannot be accessed by non-admin', function () {
    $this->actingAs($this->student)
        ->get(route('admin.internships.registrations.pending'))
        ->assertForbidden();
});

it('admin can open process modal for pending registration', function () {
    $mentee = Mentee::factory()->create(['user_id' => $this->student->id]);
    $registration = Registration::factory()->create([
        'mentee_id' => $mentee->id,
        'internship_id' => $this->internship->id,
        'status' => 'pending',
    ]);
    $registration->setStatus('pending', 'test');

    Livewire::actingAs($this->admin)
        ->test(RegistrationVerification::class)
        ->call('process', $registration->id)
        ->assertSet('showProcessModal', true)
        ->assertSet('processId', $registration->id);
});

it('admin can process a pending registration via the component', function () {
    $mentor = Mentor::factory()->create(['type' => 'school_teacher']);

    $mentee = Mentee::factory()->create(['user_id' => $this->student->id]);
    $registration = Registration::factory()->create([
        'mentee_id' => $mentee->id,
        'internship_id' => $this->internship->id,
        'status' => 'pending',
    ]);
    $registration->setStatus('pending', 'test');

    Livewire::actingAs($this->admin)
        ->test(RegistrationVerification::class)
        ->call('process', $registration->id)
        ->set('placement_id', $this->placement->id)
        ->set('mentor_ids', [$mentor->id])
        ->call('confirmProcess')
        ->assertHasNoErrors()
        ->assertSet('showProcessModal', false);

    $registration->refresh();

    expect($registration->placement_id)->toBe($this->placement->id);
    expect($registration->hasStatus('active'))->toBeTrue();
    expect($registration->mentors()->count())->toBe(1);
    expect($this->placement->fresh()->filled_quota)->toBe(1);
});

it('validates placement is required when processing', function () {
    $mentee = Mentee::factory()->create(['user_id' => $this->student->id]);
    $registration = Registration::factory()->create([
        'mentee_id' => $mentee->id,
        'internship_id' => $this->internship->id,
        'status' => 'pending',
    ]);
    $registration->setStatus('pending', 'test');

    Livewire::actingAs($this->admin)
        ->test(RegistrationVerification::class)
        ->call('process', $registration->id)
        ->call('confirmProcess')
        ->assertHasErrors(['placement_id']);
});

it('fails gracefully when placement is full via component', function () {
    $fullPlacement = Placement::factory()->create([
        'internship_id' => $this->internship->id,
        'quota' => 1,
        'filled_quota' => 1,
    ]);

    $mentee = Mentee::factory()->create(['user_id' => $this->student->id]);
    $registration = Registration::factory()->create([
        'mentee_id' => $mentee->id,
        'internship_id' => $this->internship->id,
        'status' => 'pending',
    ]);
    $registration->setStatus('pending', 'test');

    Livewire::actingAs($this->admin)
        ->test(RegistrationVerification::class)
        ->call('process', $registration->id)
        ->set('placement_id', $fullPlacement->id)
        ->call('confirmProcess')
        ->assertHasNoErrors('placement_id')
        ->assertSet('showProcessModal', true);

    $registration->refresh();

    expect($registration->hasStatus('pending'))->toBeTrue();
    expect($registration->placement_id)->toBeNull();
});

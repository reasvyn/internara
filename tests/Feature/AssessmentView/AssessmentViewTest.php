<?php

declare(strict_types=1);

use App\Livewire\Assessment\AssessmentView;
use App\Models\Assessment;
use App\Models\Internship;
use App\Models\Registration;
use App\Models\Rubric;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['super_admin', 'admin', 'teacher', 'student', 'supervisor'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $this->student = User::factory()->create()->assignRole('student');
    $this->otherStudent = User::factory()->create()->assignRole('student');
    $this->admin = User::factory()->create()->assignRole('admin');

    $this->internship = Internship::factory()->create(['status' => 'active']);

    $this->rubric = Rubric::factory()->create([
        'internship_id' => $this->internship->id,
        'is_active' => true,
    ]);
});

it('shows empty state when student has no assessments', function () {
    Livewire::actingAs($this->student)
        ->test(AssessmentView::class)
        ->assertSee('No assessments yet');
});

it('student can see their finalized assessment', function () {
    $registration = Registration::factory()->create([
        'internship_id' => $this->internship->id,
        'status' => 'active',
    ]);
    $registration->setStatus('active', 'test');

    Assessment::factory()->create([
        'registration_id' => $registration->id,
        'rubric_id' => $this->rubric->id,
        'score' => 85.5,
        'finalized_at' => now(),
    ]);

    // The registration's mentee user needs to be the student
    $registration->mentee->user()->associate($this->student);
    $registration->mentee->save();

    Livewire::actingAs($this->student)
        ->test(AssessmentView::class)
        ->assertSee('85.5');
});

it('student cannot see other students assessments', function () {
    $registration = Registration::factory()->create([
        'internship_id' => $this->internship->id,
        'status' => 'active',
    ]);
    $registration->setStatus('active', 'test');

    Assessment::factory()->create([
        'registration_id' => $registration->id,
        'rubric_id' => $this->rubric->id,
        'score' => 92.0,
        'finalized_at' => now(),
    ]);

    Livewire::actingAs($this->otherStudent)
        ->test(AssessmentView::class)
        ->assertDontSee('92.0');
});

it('shows no assessments when only unfinalized exist', function () {
    $registration = Registration::factory()->create([
        'internship_id' => $this->internship->id,
        'status' => 'active',
    ]);
    $registration->setStatus('active', 'test');

    Assessment::factory()->create([
        'registration_id' => $registration->id,
        'rubric_id' => $this->rubric->id,
        'score' => 75.0,
        'finalized_at' => null,
    ]);

    $registration->mentee->user()->associate($this->student);
    $registration->mentee->save();

    Livewire::actingAs($this->student)
        ->test(AssessmentView::class)
        ->assertSee('No assessments yet')
        ->assertSee('Your finalized assessment results will appear here');
});

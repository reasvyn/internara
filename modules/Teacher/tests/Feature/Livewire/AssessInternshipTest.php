<?php

declare(strict_types=1);

namespace Modules\Teacher\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Permission\Models\Role;
use Modules\Teacher\Livewire\AssessInternship;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
});

test('teacher can view assessment page', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = app(RegistrationService::class)
        ->factory()
        ->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
        ]);

    $this->actingAs($teacher)
        ->get(route('teacher.assess', $registration->id))
        ->assertStatus(200);
});

test('teacher can submit assessment', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = app(RegistrationService::class)
        ->factory()
        ->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
        ]);

    Livewire::actingAs($teacher)
        ->test(AssessInternship::class, ['registrationId' => $registration->id])
        ->set('criteria.discipline', 90)
        ->set('criteria.teamwork', 80)
        ->set('criteria.technical_skill', 85)
        ->set('criteria.attitude', 95)
        ->set('feedback', 'Good student')
        ->call('save')
        ->assertRedirect(route('teacher.dashboard'));

    $this->assertDatabaseHas('assessments', [
        'registration_id' => $registration->id,
        'type' => 'teacher',
        'evaluator_id' => $teacher->id,
        'score' => 87.5, // (90+80+85+95)/4
    ]);
});

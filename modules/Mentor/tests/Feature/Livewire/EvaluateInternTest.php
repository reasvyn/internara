<?php

declare(strict_types=1);

namespace Modules\Mentor\Tests\Feature\Livewire;

use Livewire\Livewire;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Mentor\Livewire\EvaluateIntern;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'mentor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
});

test('mentor can view evaluation page', function () {
    $mentor = User::factory()->create();
    $mentor->assignRole('mentor');

    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = app(RegistrationService::class)
        ->factory()
        ->create([
            'mentor_id' => $mentor->id,
            'student_id' => $student->id,
        ]);

    $this->actingAs($mentor)
        ->get(route('mentor.evaluate', $registration->id))
        ->assertStatus(200);
});

test('mentor can submit evaluation', function () {
    \Modules\Permission\Models\Permission::firstOrCreate([
        'name' => 'assessment.manage',
        'guard_name' => 'web',
    ]);
    $mentor = User::factory()->create();
    $mentor->assignRole('mentor');
    $mentor->givePermissionTo('assessment.manage');

    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = app(RegistrationService::class)
        ->factory()
        ->create([
            'mentor_id' => $mentor->id,
            'student_id' => $student->id,
        ]);

    Livewire::actingAs($mentor)
        ->test(EvaluateIntern::class, ['registrationId' => $registration->id])
        ->set('criteria.work_quality', 95)
        ->set('criteria.initiative', 85)
        ->set('criteria.punctuality', 90)
        ->set('criteria.communication', 80)
        ->set('feedback', 'Excellent intern')
        ->call('save')
        ->assertRedirect(route('mentor.dashboard'));

    $this->assertDatabaseHas('assessments', [
        'registration_id' => $registration->id,
        'type' => 'mentor',
        'evaluator_id' => $mentor->id,
        'score' => 87.5, // (95+85+90+80)/4
    ]);
});

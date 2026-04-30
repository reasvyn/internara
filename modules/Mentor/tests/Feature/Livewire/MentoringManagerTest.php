<?php

declare(strict_types=1);

namespace Modules\Mentor\Tests\Feature\Livewire;

use Livewire\Livewire;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Mentor\Livewire\MentoringManager;
use Modules\User\Models\User;

test('component renders successfully with registration data', function () {
    $registration = InternshipRegistration::factory()->create();

    Livewire::test(MentoringManager::class, ['registrationId' => $registration->id])
        ->assertStatus(200)
        ->assertViewIs('mentor::livewire.mentoring-manager')
        ->assertSet('registrationId', $registration->id);
});

test('it validates required fields when recording a visit', function () {
    $registration = InternshipRegistration::factory()->create();

    Livewire::test(MentoringManager::class, ['registrationId' => $registration->id])
        ->set('visit_date', '')
        ->call('recordVisit')
        ->assertHasErrors(['visit_date' => 'required']);
});

test('it can record a mentoring visit', function () {
    $teacher = User::factory()->create();
    $this->actingAs($teacher);

    $registration = InternshipRegistration::factory()->create([
        'teacher_id' => $teacher->id,
    ]);

    Livewire::test(MentoringManager::class, ['registrationId' => $registration->id])
        ->set('visit_date', now()->toDateString())
        ->set('visit_notes', 'Good progress observed.')
        ->call('recordVisit')
        ->assertHasNoErrors()
        ->assertSet('visitModal', false)
        ->assertSet('visit_notes', '');

    $this->assertDatabaseHas('mentoring_visits', [
        'registration_id' => $registration->id,
        'teacher_id' => $teacher->id,
        'visit_date' => now()->toDateString(),
        'notes' => 'Good progress observed.',
    ]);
});

test('it validates required fields when recording a log', function () {
    $registration = InternshipRegistration::factory()->create();

    Livewire::test(MentoringManager::class, ['registrationId' => $registration->id])
        ->set('log_subject', '')
        ->set('log_content', '')
        ->call('recordLog')
        ->assertHasErrors(['log_subject' => 'required', 'log_content' => 'required']);
});

test('it can record a mentoring log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $registration = InternshipRegistration::factory()->create();

    Livewire::test(MentoringManager::class, ['registrationId' => $registration->id])
        ->set('log_subject', 'Weekly Review')
        ->set('log_content', 'Discussed the project timeline.')
        ->set('log_type', 'feedback')
        ->call('recordLog')
        ->assertHasNoErrors()
        ->assertSet('logModal', false)
        ->assertSet('log_subject', '')
        ->assertSet('log_content', '')
        ->assertSet('log_type', 'feedback');

    $this->assertDatabaseHas('mentoring_logs', [
        'registration_id' => $registration->id,
        'causer_id' => $user->id,
        'subject' => 'Weekly Review',
        'type' => 'feedback',
    ]);
});

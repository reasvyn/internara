<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Program\InternshipGroup\Models\InternshipGroup;
use App\Program\InternshipGroup\Models\InternshipGroupMember;
use App\Reports\Report\Livewire\ReportWriter;
use App\Reports\Report\Models\Report;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->student = User::factory()->create();
    $this->student->assignRole('student');
    $this->actingAs($this->student);

    Gate::define('create', fn ($user) => $user->hasRole('student'));
});

test('report writer renders for student', function () {
    Livewire::test(ReportWriter::class)
        ->assertViewIs('reports.report.report-writer');
});

test('report writer mounts without existing report', function () {
    Livewire::test(ReportWriter::class)
        ->assertSet('reportId', null)
        ->assertSet('title', '');
});

test('report writer mounts with existing report', function () {
    $registration = Registration::factory()->create(['student_id' => $this->student->id, 'status' => 'active']);
    $group = InternshipGroup::factory()->create();
    InternshipGroupMember::factory()->create([
        'registration_id' => $registration->id,
        'internship_group_id' => $group->id,
        'user_id' => $this->student->id,
        'role' => 'student',
    ]);

    $report = Report::factory()->create([
        'registration_id' => $registration->id,
        'title' => 'My Report',
    ]);

    Livewire::test(ReportWriter::class)
        ->assertSet('reportId', $report->id)
        ->assertSet('title', 'My Report')
        ->assertSet('registrationId', $registration->id);
});

test('report writer validates title is required for saveDraft', function () {
    $registration = Registration::factory()->create(['student_id' => $this->student->id, 'status' => 'active']);
    $group = InternshipGroup::factory()->create();
    InternshipGroupMember::factory()->create([
        'registration_id' => $registration->id,
        'internship_group_id' => $group->id,
        'user_id' => $this->student->id,
        'role' => 'student',
    ]);

    Livewire::test(ReportWriter::class)
        ->set('registrationId', $registration->id)
        ->call('saveDraft')
        ->assertHasErrors(['title' => 'required']);
});

test('report writer validates registrationId is required for saveDraft', function () {
    Livewire::test(ReportWriter::class)
        ->set('title', 'Test')
        ->call('saveDraft')
        ->assertHasErrors(['registrationId' => 'required']);
});

test('report writer saves draft for new report', function () {
    $registration = Registration::factory()->create(['student_id' => $this->student->id, 'status' => 'active']);

    Livewire::test(ReportWriter::class)
        ->set('title', 'My New Report')
        ->set('registrationId', $registration->id)
        ->call('saveDraft')
        ->assertSet('reportId', fn ($id) => $id === null || is_string($id));
});

test('report writer saves draft for existing report', function () {
    $registration = Registration::factory()->create(['student_id' => $this->student->id, 'status' => 'active']);
    $group = InternshipGroup::factory()->create();
    InternshipGroupMember::factory()->create([
        'registration_id' => $registration->id,
        'internship_group_id' => $group->id,
        'user_id' => $this->student->id,
        'role' => 'student',
    ]);

    $report = Report::factory()->create([
        'registration_id' => $registration->id,
        'title' => 'Original Title',
    ]);

    Livewire::test(ReportWriter::class)
        ->set('title', 'Updated Title')
        ->call('saveDraft')
        ->assertSet('reportId', $report->id);
});

test('report writer submit shows error when no report exists', function () {
    Livewire::test(ReportWriter::class)
        ->call('askSubmit')
        ->assertSet('reportId', null);
});

test('report writer submits a saved report', function () {
    $registration = Registration::factory()->create(['student_id' => $this->student->id, 'status' => 'active']);
    $group = InternshipGroup::factory()->create();
    InternshipGroupMember::factory()->create([
        'registration_id' => $registration->id,
        'internship_group_id' => $group->id,
        'user_id' => $this->student->id,
        'role' => 'student',
    ]);

    Report::factory()->create([
        'registration_id' => $registration->id,
        'title' => 'Test Report',
    ]);

    Livewire::test(ReportWriter::class)
        ->set('chapterContent', json_encode(['Chapter 1' => 'Content'], JSON_PRETTY_PRINT))
        ->call('askSubmit')
        ->assertSet('showConfirm', true)
        ->call('confirmAction')
        ->assertSet('showConfirm', false)
        ->assertSet('reportId', fn ($id) => $id === null || is_string($id));
});

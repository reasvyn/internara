<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Assignment\Database\Seeders\AssignmentSeeder;
use Modules\Assignment\Livewire\AssignmentSubmission;
use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Models\Submission;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(AssignmentSeeder::class);

    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    $this->student = User::factory()->create();
    $this->student->assignRole('student');

    $this->internship = Internship::factory()->create();
    $this->registration = InternshipRegistration::factory()->create([
        'student_id' => $this->student->id,
        'internship_id' => $this->internship->id,
    ]);
    $this->registration->setStatus('active');

    // Create assignments for this internship
    app(\Modules\Assignment\Services\Contracts\AssignmentService::class)->createDefaults(
        $this->internship->id,
    );

    $this->actingAs($this->student);
});

test('student can see assignments page', function () {
    Livewire::test(AssignmentSubmission::class)
        ->assertOk()
        ->assertSee('Laporan Kegiatan PKL')
        ->assertSee('Presentasi Kegiatan PKL');
});

test('student can submit a file for a report assignment', function () {
    $assignment = Assignment::where('title', 'Laporan Kegiatan PKL')->first();
    $file = UploadedFile::fake()->create('report.pdf', 1000);

    Livewire::test(AssignmentSubmission::class)
        ->set("uploads.{$assignment->id}", $file)
        ->call('submit', $assignment->id)
        ->assertHasNoErrors()
        ->assertDispatched('notify', message: __('assignment::ui.success_submitted'));

    $this->assertDatabaseHas('submissions', [
        'assignment_id' => $assignment->id,
        'registration_id' => $this->registration->id,
        'student_id' => $this->student->id,
    ]);

    $submission = Submission::where('assignment_id', $assignment->id)->first();
    expect($submission->getFirstMediaUrl('file'))->not->toBeEmpty();
});

test('student can submit text for a custom assignment', function () {
    $customType = \Modules\Assignment\Models\AssignmentType::create([
        'name' => 'Refleksi Mingguan',
        'slug' => 'refleksi-mingguan',
    ]);

    $assignment = Assignment::create([
        'assignment_type_id' => $customType->id,
        'internship_id' => $this->internship->id,
        'title' => 'Refleksi Mingguan',
        'is_mandatory' => false,
    ]);

    Livewire::test(AssignmentSubmission::class)
        ->set("contents.{$assignment->id}", 'Ini adalah refleksi saya.')
        ->call('submit', $assignment->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('submissions', [
        'assignment_id' => $assignment->id,
        'content' => 'Ini adalah refleksi saya.',
    ]);
});

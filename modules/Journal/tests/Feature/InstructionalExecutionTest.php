<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Assessment\Models\Competency;
use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Journal\Services\Contracts\JournalService;
use Modules\User\Models\User;


beforeEach(function () {
    $this->journalService = app(JournalService::class);
});

test('it can sync competencies when creating a journal entry', function () {
    $student = User::factory()->create();
    $registration = InternshipRegistration::factory()->create(['student_id' => $student->id]);
    $competency = Competency::create([
        'name' => 'Web Development',
        'slug' => 'web-dev',
        'category' => 'technical',
    ]);

    $data = [
        'student_id' => $student->id,
        'registration_id' => $registration->id,
        'date' => now()->format('Y-m-d'),
        'work_topic' => 'Building API',
        'activity_description' => 'Used Laravel',
        'competency_ids' => [$competency->id],
    ];

    $entry = $this->journalService->create($data);

    $this->assertDatabaseHas('journal_competency', [
        'journal_entry_id' => $entry->id,
        'competency_id' => $competency->id,
    ]);
});

test('it enforces dynamic submission window', function () {
    setting(['journal_submission_window' => 3]);

    $student = User::factory()->create();
    $registration = InternshipRegistration::factory()->create([
        'student_id' => $student->id,
        'start_date' => now()->subDays(10)->format('Y-m-d'),
        'end_date' => now()->addMonth()->format('Y-m-d'),
    ]);

    $data = [
        'student_id' => $student->id,
        'registration_id' => $registration->id,
        'date' => now()->subDays(5)->format('Y-m-d'), // 5 days ago, window is 3
        'work_topic' => 'Too late topic',
        'activity_description' => 'Old work',
    ];

    expect(fn() => $this->journalService->create($data))->toThrow(
        AppException::class,
        'journal::exceptions.submission_window_expired',
    );
});

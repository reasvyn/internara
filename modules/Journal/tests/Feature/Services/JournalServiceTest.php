<?php

declare(strict_types=1);

use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Journal\Models\JournalEntry;
use Modules\Journal\Services\Contracts\JournalService;
use Modules\User\Models\User;

beforeEach(function () {
    $this->journalService = app(JournalService::class);
});

test('it can create a journal entry', function () {
    $student = User::factory()->create();
    $registration = InternshipRegistration::factory()->create(['student_id' => $student->id]);

    $data = [
        'student_id' => $student->id,
        'registration_id' => $registration->id,
        'date' => now()->format('Y-m-d'),
        'work_topic' => 'Test Topic',
        'activity_description' => 'Test Description',
        'basic_competence' => 'Test Competence',
        'character_values' => 'Test Characters',
        'reflection' => 'Test Reflection',
    ];

    $entry = $this->journalService->create($data);

    expect($entry)->toBeInstanceOf(JournalEntry::class);
    expect($entry->work_topic)->toBe('Test Topic');
    $this->assertDatabaseHas('journal_entries', [
        'id' => $entry->id,
        'work_topic' => 'Test Topic',
    ]);
});

test('it can submit a journal entry', function () {
    $entry = JournalEntry::factory()->create();
    $entry->setStatus('draft');

    $this->journalService->submit($entry->id);

    $this->assertDatabaseHas('statuses', [
        'model_id' => $entry->id,
        'model_type' => JournalEntry::class,
        'name' => 'submitted',
    ]);
});

test('it can approve a journal entry', function () {
    $entry = JournalEntry::factory()->create();
    $entry->setStatus('submitted');

    $this->journalService->approve($entry->id, 'Good job!');

    $this->assertDatabaseHas('statuses', [
        'model_id' => $entry->id,
        'model_type' => JournalEntry::class,
        'name' => 'approved',
        'reason' => 'Good job!',
    ]);
});

test('it cannot update an approved journal entry', function () {
    $entry = JournalEntry::factory()->create();
    $entry->setStatus('approved');

    expect(
        fn () => $this->journalService->update($entry->id, ['work_topic' => 'New Topic']),
    )->toThrow(AppException::class);
});

test(
    'it throws exception when creating a journal entry before the internship period starts',
    function () {
        $student = User::factory()->create();
        $registration = InternshipRegistration::factory()->create([
            'student_id' => $student->id,
            'start_date' => now()->addDay()->format('Y-m-d'), // Starts tomorrow
            'end_date' => now()->addMonth()->format('Y-m-d'),
        ]);

        $data = [
            'student_id' => $student->id,
            'registration_id' => $registration->id,
            'date' => now()->format('Y-m-d'),
            'work_topic' => 'Test Topic',
            'activity_description' => 'Test Description',
            'basic_competence' => 'Test Competence',
        ];

        expect(fn () => $this->journalService->create($data))->toThrow(
            AppException::class,
            'journal::exceptions.outside_internship_period',
        );
    },
);

test(
    'it throws exception when creating a journal entry after the internship period ends',
    function () {
        $student = User::factory()->create();
        $registration = InternshipRegistration::factory()->create([
            'student_id' => $student->id,
            'start_date' => now()->subMonth()->format('Y-m-d'),
            'end_date' => now()->subDay()->format('Y-m-d'), // Ended yesterday
        ]);

        $data = [
            'student_id' => $student->id,
            'registration_id' => $registration->id,
            'date' => now()->format('Y-m-d'),
            'work_topic' => 'Test Topic',
            'activity_description' => 'Test Description',
            'basic_competence' => 'Test Competence',
        ];

        expect(fn () => $this->journalService->create($data))->toThrow(
            AppException::class,
            'journal::exceptions.outside_internship_period',
        );
    },
);

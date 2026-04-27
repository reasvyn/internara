<?php

declare(strict_types=1);

namespace Modules\Journal\Tests\Feature\Telemetry;

use Illuminate\Support\Facades\Event;
use Modules\Exception\AppException;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Journal\Events\JournalArchived;
use Modules\Journal\Models\JournalEntry;
use Modules\Journal\Services\Contracts\JournalService;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

test(
    'edit lock audit: student cannot update an approved journal [STRS-01] [SYRS-F-403]',
    function () {
        $student = app(UserService::class)->factory()->create();
        $this->actingAs($student);

        $entry = JournalEntry::factory()->create();
        $entry->setStatus('approved', 'Locked by supervisor');

        expect(
            fn() => app(JournalService::class)->update($entry->id, ['work_topic' => 'Hacked']),
        )->toThrow(AppException::class, 'journal::exceptions.cannot_edit_locked_journal');
    },
);

test(
    'window enforcement audit: student cannot submit old journals [STRS-01] [SYRS-F-403]',
    function () {
        $student = app(UserService::class)->factory()->create();
        $this->actingAs($student);

        setting(['journal_submission_window' => 7]);

        $reg = InternshipRegistration::factory()->create([
            'student_id' => $student->id,
        ]);

        // Attempt to log for 10 days ago
        expect(
            fn() => app(JournalService::class)->create([
                'registration_id' => $reg->id,
                'date' => now()->subDays(10)->toDateString(),
                'work_topic' => 'Late log',
                'activity_description' => 'Late description',
            ]),
        )->toThrow(AppException::class, 'journal::exceptions.submission_window_expired');
    },
);

test(
    'soft-delete invariant: deleting a journal dispatches JournalArchived event [SYRS-NF-601]',
    function () {
        Event::fake();

        $entry = JournalEntry::factory()->create();
        $entry->setStatus('draft'); // Only drafts can be deleted

        app(JournalService::class)->delete($entry->id);

        Event::assertDispatched(JournalArchived::class);
    },
);

test(
    'immutability audit: journal is locked immediately upon first verification [STRS-02] [STRS-03] [SYRS-F-403]',
    function () {
        $entry = JournalEntry::factory()->create();
        $entry->setStatus('draft');

        // 1. Verify by Mentor (Field Verified)
        app(JournalService::class)->verifyField($entry->id);

        // 2. Attempt to Update by Student
        $student = User::factory()->create();
        $this->actingAs($student);

        expect(
            fn() => app(JournalService::class)->update($entry->id, ['work_topic' => 'Tampered']),
        )->toThrow(AppException::class, 'journal::exceptions.cannot_edit_locked_journal');
    },
);

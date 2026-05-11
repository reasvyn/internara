<?php

declare(strict_types=1);

use App\Actions\Submission\GradeSubmissionAction;
use Database\Factories\SubmissionFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('grades a submission with verified status', function () {
        $submission = SubmissionFactory::new()->create();
        $grader = UserFactory::new()->create()->assignRole('teacher');

        $result = app(GradeSubmissionAction::class)->execute(
            $submission,
            $grader,
            score: 85.0,
            status: 'verified',
            feedback: 'Great work!',
        );

        expect($result->score)->toBe(85.0)
            ->and($result->feedback)->toBe('Great work!');
    });

    it('grades a submission with revision_required status', function () {
        $submission = SubmissionFactory::new()->create();
        $grader = UserFactory::new()->create()->assignRole('teacher');

        $result = app(GradeSubmissionAction::class)->execute(
            $submission,
            $grader,
            score: 60.0,
            status: 'revision_required',
            feedback: 'Needs improvement',
        );

        expect($result->score)->toBe(60.0);
    });

    it('throws InvalidArgumentException for score out of range', function () {
        $submission = SubmissionFactory::new()->create();
        $grader = UserFactory::new()->create()->assignRole('teacher');

        expect(fn () => app(GradeSubmissionAction::class)->execute(
            $submission,
            $grader,
            score: 150,
            status: 'verified',
        ))->toThrow(InvalidArgumentException::class, 'Score must be between 0 and 100');
    });

    it('throws InvalidArgumentException for invalid grading status', function () {
        $submission = SubmissionFactory::new()->create();
        $grader = UserFactory::new()->create()->assignRole('teacher');

        expect(fn () => app(GradeSubmissionAction::class)->execute(
            $submission,
            $grader,
            score: 80,
            status: 'invalid_status',
        ))->toThrow(InvalidArgumentException::class, 'Invalid grading status');
    });

    it('throws InvalidArgumentException for unauthorized grader', function () {
        $submission = SubmissionFactory::new()->create();
        $user = UserFactory::new()->create();

        expect(fn () => app(GradeSubmissionAction::class)->execute(
            $submission,
            $user,
            score: 80,
            status: 'verified',
        ))->toThrow(InvalidArgumentException::class, 'Not authorized to grade submissions');
    });
});

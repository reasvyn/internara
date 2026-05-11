<?php

declare(strict_types=1);

use App\Actions\Assignment\VerifySubmissionAction;
use App\Notifications\SubmissionFeedbackNotification;
use Database\Factories\SubmissionFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeAll(function () {
    require_once getcwd().'/app/Notifications/Assignment/SubmissionFeedbackNotification.php';
    class_alias(
        App\Notifications\Assignment\SubmissionFeedbackNotification::class,
        SubmissionFeedbackNotification::class,
    );
});

beforeEach(function () {
    Notification::fake();
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('verifies a submission', function () {
        $submission = SubmissionFactory::new()->create();
        $verifier = UserFactory::new()->create()->assignRole('teacher');

        $result = app(VerifySubmissionAction::class)->execute(
            $submission,
            $verifier,
            'verified',
            'Great work!',
        );

        expect($result->status->value)->toBe('verified')
            ->and($result->metadata['feedback'])->toBe('Great work!');
    });

    it('marks submission as revision required', function () {
        $submission = SubmissionFactory::new()->create();
        $verifier = UserFactory::new()->create()->assignRole('teacher');

        $result = app(VerifySubmissionAction::class)->execute(
            $submission,
            $verifier,
            'revision_required',
            'Please revise',
        );

        expect($result->status->value)->toBe('revision_required');
    });

    it('throws for invalid verification status', function () {
        $submission = SubmissionFactory::new()->create();
        $verifier = UserFactory::new()->create()->assignRole('teacher');

        expect(fn () => app(VerifySubmissionAction::class)->execute(
            $submission,
            $verifier,
            'invalid_status',
        ))->toThrow(InvalidArgumentException::class, 'Invalid verification status');
    });

    it('throws if user is not authorized', function () {
        $submission = SubmissionFactory::new()->create();
        $user = UserFactory::new()->create();

        expect(fn () => app(VerifySubmissionAction::class)->execute(
            $submission,
            $user,
            'verified',
        ))->toThrow(InvalidArgumentException::class, 'Not authorized to verify submissions');
    });
});

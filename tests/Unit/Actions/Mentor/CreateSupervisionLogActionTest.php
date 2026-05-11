<?php

declare(strict_types=1);

use App\Actions\Mentor\CreateSupervisionLogAction;
use App\Models\SupervisionLog;
use Database\Factories\RegistrationFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeAll(function () {
    require_once getcwd().'/app/Models/SupervisionLog.php';
    class_alias(
        SupervisionLog::class,
        App\Models\Mentor\SupervisionLog::class,
    );
});

describe('execute', function () {
    it('creates a supervision log', function () {
        $teacher = UserFactory::new()->create();
        $registration = RegistrationFactory::new()->create();

        $log = app(CreateSupervisionLogAction::class)->execute(
            $teacher,
            $registration->id,
            [
                'date' => '2026-05-01',
                'topic' => 'First site visit',
                'notes' => 'Student is progressing well.',
            ],
        );

        expect($log)->toBeInstanceOf(SupervisionLog::class)
            ->and($log->registration_id)->toBe($registration->id)
            ->and($log->supervisor_id)->toBe($teacher->id)
            ->and($log->status)->toBe('in_progress');
    });
});

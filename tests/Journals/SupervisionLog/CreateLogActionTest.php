<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Journals\SupervisionLog\Actions\CreateLogAction;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('CreateLogAction', function () {
    test('creates a supervision log in draft status', function () {
        $student = User::factory()->create();
        $supervisor = User::factory()->create();
        $registration = Registration::factory()->create();

        $log = app(CreateLogAction::class)->execute(
            $student,
            $registration->id,
            [
                'supervisor_id' => $supervisor->id,
                'topic' => 'Weekly progress check',
                'notes' => 'Good progress on project',
            ],
        );

        expect($log)->toBeInstanceOf(SupervisionLog::class);
        expect($log->registration_id)->toBe($registration->id);
        expect($log->supervisor_id)->toBe($supervisor->id);
        expect($log->topic)->toBe('Weekly progress check');
        expect($log->notes)->toBe('Good progress on project');
        expect($log->status->value)->toBe('draft');
    });

    test('creates log with supervisor only data', function () {
        $student = User::factory()->create();
        $supervisor = User::factory()->create();
        $registration = Registration::factory()->create();

        $log = app(CreateLogAction::class)->execute(
            $student,
            $registration->id,
            [
                'supervisor_id' => $supervisor->id,
                'notes' => 'Quick check-in',
            ],
        );

        expect($log->date)->not->toBeNull();
        expect($log->topic)->toBeNull();
    });
});

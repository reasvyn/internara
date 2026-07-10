<?php

declare(strict_types=1);

use App\Enrollment\Registration\Models\Registration;
use App\Guidance\MonitoringVisit\Actions\CreateVisitAction;
use App\Guidance\MonitoringVisit\Enums\VisitMethod;
use App\Guidance\MonitoringVisit\Models\MonitoringVisit;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('CreateVisitAction', function () {
    test('creates a monitoring visit with minimum data', function () {
        $teacher = User::factory()->create();
        $registration = Registration::factory()->create();

        $visit = app(CreateVisitAction::class)->execute(
            $teacher,
            $registration->id,
            ['method' => VisitMethod::SITE_VISIT->value],
        );

        expect($visit)->toBeInstanceOf(MonitoringVisit::class);
        expect($visit->teacher_id)->toBe($teacher->id);
        expect($visit->registration_id)->toBe($registration->id);
        expect($visit->method)->toBe(VisitMethod::SITE_VISIT);
        expect($visit->is_verified)->toBeFalse();
    });

    test('creates a monitoring visit with full data', function () {
        $teacher = User::factory()->create();
        $registration = Registration::factory()->create();

        $visit = app(CreateVisitAction::class)->execute(
            $teacher,
            $registration->id,
            [
                'method' => VisitMethod::VIRTUAL_MEETING->value,
                'location' => 'Zoom Meeting',
                'duration_minutes' => 45,
                'notes' => 'Discussed progress',
                'student_condition' => 'Good',
                'company_feedback' => 'Positive',
                'follow_up_actions' => 'Continue monitoring',
            ],
        );

        expect($visit->method)->toBe(VisitMethod::VIRTUAL_MEETING);
        expect($visit->location)->toBe('Zoom Meeting');
        expect($visit->duration_minutes)->toBe(45);
        expect($visit->notes)->toBe('Discussed progress');
        expect($visit->student_condition)->toBe('Good');
        expect($visit->company_feedback)->toBe('Positive');
        expect($visit->follow_up_actions)->toBe('Continue monitoring');
    });
});

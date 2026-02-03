<?php

declare(strict_types=1);

namespace Modules\Assessment\Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

test('authorized student can download certificate', function () {
    // Arrange
    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = app(RegistrationService::class)
        ->factory()
        ->create([
            'student_id' => $student->id,
        ]);

    // Mock Readiness
    $this->mock(AssessmentService::class, function ($mock) {
        $mock->shouldReceive('getReadinessStatus')->andReturn([
            'is_ready' => true,
            'missing' => [],
        ]);

        $mock->shouldReceive('getScoreCard')->andReturn([
            'mentor' => null,
            'teacher' => null,
            'compliance' => ['final_score' => 0],
            'final_grade' => 0,
        ]);
    });

    // Act
    $response = $this->actingAs($student)->get(route('assessment.certificate', $registration->id));

    // Assert
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader(
        'Content-Disposition',
        'attachment; filename="certificate-'.$student->username.'.pdf"',
    );
});

test('unauthorized user cannot download certificate', function () {
    // Arrange
    $otherUser = User::factory()->create();
    $registration = app(RegistrationService::class)->factory()->create();

    // Act
    $response = $this->actingAs($otherUser)->get(
        route('assessment.certificate', $registration->id),
    );

    // Assert
    $response->assertStatus(403);
});

test('verification route works with signed URL', function () {
    // Arrange
    $student = User::factory()->create();
    $student->assignRole('student');

    $registration = app(RegistrationService::class)
        ->factory()
        ->create([
            'student_id' => $student->id,
        ]);

    $url = URL::signedRoute('assessment.verify', ['registration' => $registration->id]);

    // Act
    $response = $this->get($url);

    // Assert
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('verification route fails without signature', function () {
    // Arrange
    $registration = app(RegistrationService::class)->factory()->create();
    $url = route('assessment.verify', ['registration' => $registration->id]);

    // Act
    $response = $this->get($url);

    // Assert
    $response->assertStatus(403);
});

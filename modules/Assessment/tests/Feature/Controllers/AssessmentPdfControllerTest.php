<?php

declare(strict_types=1);

namespace Modules\Assessment\Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Internship\Services\Contracts\InternshipRegistrationService;
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

    $registration = app(InternshipRegistrationService::class)
        ->factory()
        ->create([
            'student_id' => $student->id,
        ]);

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
    $registration = app(InternshipRegistrationService::class)->factory()->create();

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

    $registration = app(InternshipRegistrationService::class)
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
    $registration = app(InternshipRegistrationService::class)->factory()->create();
    $url = route('assessment.verify', ['registration' => $registration->id]);

    // Act
    $response = $this->get($url);

    // Assert
    $response->assertStatus(403);
});

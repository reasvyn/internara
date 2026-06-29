<?php

declare(strict_types=1);

use App\Certification\Certificate\Models\Certificate;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('guest cannot download certificate', function () {
    $certificate = Certificate::factory()->create();

    $response = $this->get(route('certificates.download', $certificate));

    $response->assertStatus(302);
});

test('student can download own certificate', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $this->actingAs($student);

    $registration = Registration::factory()->create(['student_id' => $student->id]);
    \App\Program\InternshipGroup\Models\InternshipGroupMember::factory()->create([
        'registration_id' => $registration->id,
        'user_id' => $student->id,
    ]);
    $certificate = Certificate::factory()->create([
        'registration_id' => $registration->id,
    ]);

    $response = $this->get(route('certificates.download', $certificate));

    $response->assertStatus(200);
});

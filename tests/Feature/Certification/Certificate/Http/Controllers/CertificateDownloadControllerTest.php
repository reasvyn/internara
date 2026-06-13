<?php

declare(strict_types=1);

use App\Certification\Certificate\Models\Certificate;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('guest cannot download certificate', function () {
    $certificate = Certificate::factory()->create();

    $response = $this->get(route('certificates.download', $certificate));

    $response->assertStatus(403);
});

test('student can download own certificate', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $this->actingAs($student);

    $certificate = Certificate::factory()->create();

    $response = $this->get(route('certificates.download', $certificate));

    $response->assertStatus(200);
});

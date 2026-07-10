<?php

declare(strict_types=1);

use App\Certification\Certificate\Enums\CertificateStatus;
use App\Certification\Certificate\Models\Certificate;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('certificate belongs to registration', function () {
    $registration = Registration::factory()->create();
    $certificate = Certificate::factory()->create(['registration_id' => $registration->id]);

    expect($certificate->registration)->toBeInstanceOf(Registration::class);
});

test('certificate belongs to issuer', function () {
    $user = User::factory()->create();
    $certificate = Certificate::factory()->create(['issued_by' => $user->id]);

    expect($certificate->issuer)->toBeInstanceOf(User::class);
});

test('default status is issued', function () {
    $certificate = Certificate::factory()->create();

    expect($certificate->status)->toBeInstanceOf(CertificateStatus::class);
    expect($certificate->status->value)->toBe('issued');
});

test('casts status as enum', function () {
    $certificate = Certificate::factory()->create();

    expect($certificate->status)->toBeInstanceOf(CertificateStatus::class);
});

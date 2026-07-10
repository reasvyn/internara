<?php

declare(strict_types=1);

use App\Certification\Certificate\Actions\IssueCertificateAction;
use App\Certification\Certificate\Models\Certificate;
use App\Certification\Certificate\Models\CertificateTemplate;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('issues certificate for registration', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $registration = Registration::factory()->create();
    $template = CertificateTemplate::factory()->create();

    $certificate = app(IssueCertificateAction::class)->execute($registration, $template);

    expect($certificate)->toBeInstanceOf(Certificate::class);
    expect($certificate->certificate_number)->not->toBeNull();
});

test('generates unique certificate numbers', function () {
    $this->actingAs(User::factory()->create());

    $registration1 = Registration::factory()->create();
    $registration2 = Registration::factory()->create();
    $template = CertificateTemplate::factory()->create();

    $cert1 = app(IssueCertificateAction::class)->execute($registration1, $template);
    $cert2 = app(IssueCertificateAction::class)->execute($registration2, $template);

    expect($cert1->certificate_number)->not->toBe($cert2->certificate_number);
});

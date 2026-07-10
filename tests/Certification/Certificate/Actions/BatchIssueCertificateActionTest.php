<?php

declare(strict_types=1);

use App\Certification\Certificate\Actions\BatchIssueCertificateAction;
use App\Certification\Certificate\Models\CertificateTemplate;
use App\Enrollment\Registration\Models\Registration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('batch issues certificates for multiple registrations', function () {
    $registrations = Registration::factory()->count(3)->create();
    $template = CertificateTemplate::factory()->create();

    $result = app(BatchIssueCertificateAction::class)->execute(
        $registrations->pluck('id')->toArray(),
        $template,
    );

    expect($result['success'])->toBe(3);
    expect($result['failed'])->toBe(0);
});

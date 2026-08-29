<?php

declare(strict_types=1);

use App\Modules\Certification\Domain\Certificate\Actions\IssueCertificateAction;
use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('J0M04-FR-CI5: template content is snapshotted on the certificate at issuance', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $registration = Registration::factory()->create();
    $template = CertificateTemplate::factory()->create([
        'content_template' => '<h1>Certificate</h1><p>{student_name}</p>',
        'is_active' => true,
    ]);

    $certificate = app(IssueCertificateAction::class)->execute($registration, $template);

    expect($certificate->registration_id)->toBe($registration->id)
        ->and($certificate->template_content)->toBe($template->content_template)
        ->and($certificate->issued_by)->toBe($user->id)
        ->and($certificate->certificate_number)->not->toBeNull()
        ->and($certificate->qr_hash)->not->toBeNull();
});

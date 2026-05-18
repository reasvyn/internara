<?php

declare(strict_types=1);

use App\Actions\Certificate\IssueCertificateAction;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('issues a certificate', function () {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $registration = Registration::factory()->create(['status' => 'active']);

        $template = CertificateTemplate::create([
            'name' => 'Standard',
            'layout' => 'portrait',
            'content_template' => '<h1>Certificate for {student_name}</h1>',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $certificate = app(IssueCertificateAction::class)->execute($registration, $template);

        expect($certificate)->toBeInstanceOf(Certificate::class)
            ->and($certificate->status->value)->toBe('issued')
            ->and($certificate->certificate_number)->not->toBeEmpty();
    });
});

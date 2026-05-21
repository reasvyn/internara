<?php

declare(strict_types=1);

use App\Domain\Certificate\Actions\BatchIssueCertificateAction;
use App\Domain\Certificate\Actions\CreateCertificateTemplateAction;
use App\Domain\Certificate\Actions\IssueCertificateAction;
use App\Domain\Certificate\Actions\RevokeCertificateAction;
use App\Domain\Certificate\Models\Certificate;
use App\Domain\Certificate\Models\CertificateTemplate;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Illuminate\Validation\ValidationException;

describe('IssueCertificateAction', function () {
    it('issues a certificate for a registration', function () {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $user = User::factory()->create();
        $mentee = Mentee::factory()->create(['user_id' => $user->id]);
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);
        $template = CertificateTemplate::factory()->create(['created_by' => $admin->id]);

        $certificate = app(IssueCertificateAction::class)->execute($registration, $template);

        expect($certificate)->toBeInstanceOf(Certificate::class)
            ->and($certificate->registration_id)->toBe($registration->id)
            ->and($certificate->template_id)->toBe($template->id)
            ->and($certificate->status->value)->toBe('issued')
            ->and($certificate->issued_by)->toBe($admin->id)
            ->and($certificate->metadata)->toHaveKey('pdf_path');
    });
});

describe('BatchIssueCertificateAction', function () {
    it('issues certificates for multiple registrations', function () {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $template = CertificateTemplate::factory()->create(['created_by' => $admin->id]);

        $registrations = [];
        foreach (range(1, 3) as $i) {
            $user = User::factory()->create();
            $mentee = Mentee::factory()->create(['user_id' => $user->id]);
            $registrations[] = Registration::factory()->create(['mentee_id' => $mentee->id]);
        }

        $ids = collect($registrations)->pluck('id')->toArray();

        $results = app(BatchIssueCertificateAction::class)->execute($ids, $template);

        expect($results['success'])->toBe(3)
            ->and($results['failed'])->toBe(0);
    });
});

describe('RevokeCertificateAction', function () {
    it('revokes an active certificate', function () {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $registration = Registration::factory()->create(['mentee_id' => Mentee::factory()->create(['user_id' => User::factory()->create()->id])->id]);
        $template = CertificateTemplate::factory()->create(['created_by' => $admin->id]);
        $certificate = Certificate::create([
            'registration_id' => $registration->id,
            'template_id' => $template->id,
            'certificate_number' => 'TEST/2026/0001',
            'status' => 'issued',
            'issued_by' => $admin->id,
            'issued_at' => now(),
        ]);

        $revoked = app(RevokeCertificateAction::class)->execute($certificate);

        expect($revoked->status->value)->toBe('revoked')
            ->and($revoked->revoked_by)->toBe($admin->id)
            ->and($revoked->revoked_at)->not->toBeNull();
    });

    it('throws when certificate is already revoked', function () {
        $registration = Registration::factory()->create(['mentee_id' => Mentee::factory()->create(['user_id' => User::factory()->create()->id])->id]);
        $template = CertificateTemplate::factory()->create(['created_by' => User::factory()->create()->id]);
        $certificate = Certificate::create([
            'registration_id' => $registration->id,
            'template_id' => $template->id,
            'certificate_number' => 'TEST/2026/0002',
            'status' => 'revoked',
            'issued_by' => User::factory()->create()->id,
            'issued_at' => now(),
        ]);

        app(RevokeCertificateAction::class)->execute($certificate);
    })->throws(RuntimeException::class, 'already been revoked');
});

describe('CreateCertificateTemplateAction', function () {
    it('creates a certificate template', function () {
        $user = User::factory()->create();

        $template = app(CreateCertificateTemplateAction::class)->execute([
            'name' => 'Standard Certificate',
            'layout' => 'portrait',
            'content_template' => '<h1>{{student_name}}</h1>',
            'created_by' => $user->id,
        ]);

        expect($template)->toBeInstanceOf(CertificateTemplate::class)
            ->and($template->name)->toBe('Standard Certificate')
            ->and($template->layout)->toBe('portrait');
    });

    it('validates template data', function () {
        app(CreateCertificateTemplateAction::class)->execute([
            'name' => 'Test',
        ]);
    })->throws(ValidationException::class);
});

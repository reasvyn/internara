<?php

declare(strict_types=1);

use App\Certification\Certificate\Actions\CreateCertificateTemplateAction;
use App\Certification\Certificate\Models\CertificateTemplate;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('creates certificate template with valid data', function () {
    $user = User::factory()->create();

    $template = app(CreateCertificateTemplateAction::class)->execute([
        'name' => 'Sertifikat Magang',
        'layout' => 'portrait',
        'content_template' => '<h1>Sertifikat</h1>',
        'created_by' => $user->id,
    ]);

    expect($template)->toBeInstanceOf(CertificateTemplate::class);
    expect($template->name)->toBe('Sertifikat Magang');
});

test('throws validation error with invalid data', function () {
    app(CreateCertificateTemplateAction::class)->execute([
        'name' => '',
        'layout' => 'invalid',
        'content_template' => '',
    ]);
})->throws(ValidationException::class);

<?php

declare(strict_types=1);

use App\Actions\Certificate\BatchIssueCertificateAction;
use App\Models\CertificateTemplate;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('issues certificates to multiple registrations', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $reg1 = Registration::factory()->create(['status' => 'active']);
    $reg2 = Registration::factory()->create(['status' => 'active']);

    $template = CertificateTemplate::create([
        'name' => 'Standard',
        'layout' => 'portrait',
        'content_template' => '<p>Certificate</p>',
        'is_active' => true,
        'created_by' => $admin->id,
    ]);

    $results = app(BatchIssueCertificateAction::class)->execute(
        [$reg1->id, $reg2->id],
        $template,
    );

    expect($results['success'])->toBe(2)
        ->and($results['failed'])->toBe(0)
        ->and($results['errors'])->toBe([]);
});

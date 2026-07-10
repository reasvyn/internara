<?php

declare(strict_types=1);

use App\Certification\Certificate\Actions\RevokeCertificateAction;
use App\Certification\Certificate\Models\Certificate;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('revokes issued certificate', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $certificate = Certificate::factory()->create();

    $result = app(RevokeCertificateAction::class)->execute($certificate);

    expect($result->status->value)->toBe('revoked');
});

test('throws when revoking already revoked certificate', function () {
    $this->actingAs(User::factory()->create());

    $certificate = Certificate::factory()->create(['status' => 'revoked']);

    app(RevokeCertificateAction::class)->execute($certificate);
})->throws(RuntimeException::class, 'This certificate has already been revoked.');

<?php

declare(strict_types=1);

use App\Certification\Certificate\Events\CertificateIssued;
use App\Certification\Certificate\Models\Certificate;

test('certificate issued event name and payload', function () {
    $certificate = new class extends Certificate {};
    $certificate->forceFill(['id' => 'c-1']);

    $event = new CertificateIssued($certificate);

    expect($event->certificate->id)->toBe('c-1');
    expect($event->eventName())->toBe('certificate.issued');
    expect($event->toPayload())->toHaveKey('certificate_id');
});

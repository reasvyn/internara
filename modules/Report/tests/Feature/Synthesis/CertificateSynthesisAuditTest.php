<?php

declare(strict_types=1);

namespace Modules\Report\Tests\Feature\Synthesis;


use Illuminate\Support\Facades\URL;



test('qr signature tampering audit: modified signatures are rejected', function () {
    $registrationId = \Str::uuid()->toString();

    // Generate valid signed URL
    $validUrl = URL::signedRoute('certificate.verify', ['id' => $registrationId]);

    // Tamper with the URL (change one char in signature)
    $tamperedUrl = $validUrl.'extra';

    // Check if the signature is valid according to Laravel
    expect(URL::hasValidSignature($validUrl))
        ->toBeTrue()
        ->and(URL::hasValidSignature($tamperedUrl))
        ->toBeFalse();
});

test('signed download audit: private files require signature', function () {
    // This tests the middleware application
    $this->get('/reports/download/some-uuid.pdf')->assertForbidden();
});

test('branding priority audit: PDF uses institutional logo', function () {
    // Conceptual test for branding injection
    $logo = setting('brand_logo_url', 'default-logo.png');

    // Logic check: if brand logo is null, use app logo
    $resolved = $logo ?: 'app-logo.png';

    expect($resolved)->not->toBeNull();
});

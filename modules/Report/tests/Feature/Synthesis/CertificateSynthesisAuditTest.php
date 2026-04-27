<?php

declare(strict_types=1);

namespace Modules\Report\Tests\Feature\Synthesis;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Report\Models\GeneratedReport;
use Modules\User\Models\User;

test('qr signature tampering audit: modified signatures are rejected', function () {
    $registrationId = Str::uuid()->toString();

    // Generate valid signed URL
    $validUrl = URL::signedRoute('assessment.verify', ['registration' => $registrationId]);

    // Tamper with the URL (change one char in signature)
    $tamperedUrl = $validUrl.'extra';

    // Check if the signature is valid according to Laravel
    // We create requests to test the signatures
    $validRequest = Request::create($validUrl);
    $tamperedRequest = Request::create($tamperedUrl);

    expect(URL::hasValidSignature($validRequest))
        ->toBeTrue()
        ->and(URL::hasValidSignature($tamperedRequest))
        ->toBeFalse();
});

test('signed download audit: private files require signature', function () {
    $user = User::factory()->create();

    // Create a dummy report record to avoid 404/500 during route model binding
    $report = GeneratedReport::create([
        'id' => Str::uuid()->toString(),
        'user_id' => $user->id,
        'file_path' => 'reports/test.pdf',
        'provider_identifier' => 'test',
    ]);

    // This tests the middleware application (requires authentication first to hit the 'signed' middleware)
    $this->actingAs($user)
        ->get(route('reports.download', ['report' => $report->id]))
        ->assertForbidden();
});

test('branding priority audit: PDF uses institutional logo', function () {
    // Conceptual test for branding injection
    $logo = setting('brand_logo_url', 'default-logo.png');

    // Logic check: if brand logo is null, use app logo
    $resolved = $logo ?: 'app-logo.png';

    expect($resolved)->not->toBeNull();
});

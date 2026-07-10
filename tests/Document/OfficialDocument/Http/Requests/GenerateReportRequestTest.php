<?php

declare(strict_types=1);

use App\Document\OfficialDocument\Http\Requests\GenerateReportRequest;

test('generate report request rules exist', function () {
    $request = new GenerateReportRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['document_id', 'registration_id']);
    expect($rules['document_id'])->toContain('required');
    expect($rules['registration_id'])->toContain('required');
});

<?php

declare(strict_types=1);

use App\Assignment\Submission\Http\Requests\SubmitAssignmentRequest;

test('submit assignment request rules exist', function () {
    $request = new SubmitAssignmentRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['content', 'file']);
    expect($rules['content'])->toContain('required');
    expect($rules['file'])->toContain('sometimes');
});

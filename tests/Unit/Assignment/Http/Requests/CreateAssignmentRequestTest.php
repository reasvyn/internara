<?php

declare(strict_types=1);

use App\Assignment\Http\Requests\CreateAssignmentRequest;

test('create assignment request rules exist', function () {
    $request = new CreateAssignmentRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['title', 'description', 'due_date', 'internship_id']);
    expect($rules['title'])->toContain('required');
    expect($rules['due_date'])->toContain('after:today');
});

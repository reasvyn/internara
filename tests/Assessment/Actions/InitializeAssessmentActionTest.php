<?php

declare(strict_types=1);

use App\Assessment\Actions\InitializeAssessmentAction;
use App\Enrollment\Registration\Models\Registration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('returns null assessment and rubric when no active rubric matches', function () {
    $registration = Registration::factory()->create();

    $result = app(InitializeAssessmentAction::class)->execute($registration->id);

    expect($result['assessment'])->toBeNull();
    expect($result['rubric'])->toBeNull();
});

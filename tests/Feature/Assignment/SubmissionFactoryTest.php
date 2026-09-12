<?php

declare(strict_types=1);

use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('T657Z-FR-SUBM-006: SubmissionFactory', function (): void {
    test('draft() sets DRAFT status with null submitted_at', function (): void {
        $submission = Submission::factory()->draft()->create();

        expect($submission->status)->toBe(SubmissionStatus::DRAFT)
            ->and($submission->submitted_at)->toBeNull();
    });
});

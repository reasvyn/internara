<?php

declare(strict_types=1);

use App\Assignment\Submission\Enums\SubmissionStatus;
use App\Assignment\Submission\Livewire\SubmissionGrading;
use App\Assignment\Submission\Models\Submission;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('T657Z: SubmissionGrading verify wiring', function () {
    test('T657Z-FR-VF1: admin verifies a submitted submission via verify()', function () {
        actingAsAdmin();

        $submission = Submission::factory()->create(['status' => 'submitted']);

        Livewire::test(SubmissionGrading::class)
            ->call('verify', $submission->id);

        $fresh = $submission->fresh();

        expect($fresh->status)->toBe(SubmissionStatus::VERIFIED)
            ->and($fresh->verified_by)->not->toBeNull()
            ->and($fresh->verified_at)->not->toBeNull();
    });

    test('T657Z-FR-VF1: verification is denied for users without the verify ability', function () {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $this->actingAs($supervisor);

        $submission = Submission::factory()->create(['status' => 'submitted']);

        Livewire::test(SubmissionGrading::class)
            ->call('verify', $submission->id);

        expect($submission->fresh()->status)->toBe(SubmissionStatus::SUBMITTED)
            ->and($submission->fresh()->verified_by)->toBeNull();
    });
});

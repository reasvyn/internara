<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Models\InternshipRequirement;
use Modules\Internship\Models\RequirementSubmission;
use Modules\Internship\Services\Contracts\PlacementService;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->placementService = app(PlacementService::class);
    $this->academicYear = '2025/2026';

    // Create required roles for isolation
    \Modules\Permission\Models\Role::create(['name' => 'student', 'guard_name' => 'web']);

    // Setup base data
    $this->student = User::factory()->create()->assignRole('student');
    $this->internship = Internship::factory()->create();
    $this->placement = InternshipPlacement::factory()->create([
        'internship_id' => $this->internship->id,
    ]);

    $this->registration = InternshipRegistration::create([
        'internship_id' => $this->internship->id,
        'placement_id' => null,
        'student_id' => $this->student->id,
        'academic_year' => $this->academicYear,
    ]);
});

it('determines eligibility based on mandatory requirements', function () {
    // 1. Create a mandatory requirement
    $requirement = InternshipRequirement::create([
        'name' => 'CV Upload',
        'type' => 'document',
        'is_mandatory' => true,
        'academic_year' => $this->academicYear,
    ]);

    // Should not be eligible initially
    expect($this->placementService->isEligibleForPlacement($this->registration->id))->toBeFalse();

    // 2. Submit proof but keep it pending
    RequirementSubmission::create([
        'registration_id' => $this->registration->id,
        'requirement_id' => $requirement->id,
        'status' => 'pending',
    ]);

    // Still not eligible
    expect($this->placementService->isEligibleForPlacement($this->registration->id))->toBeFalse();

    // 3. Verify the submission
    $this->registration
        ->requirementSubmissions()
        ->first()
        ->update(['status' => 'verified']);

    // Now should be eligible
    expect($this->placementService->isEligibleForPlacement($this->registration->id))->toBeTrue();
});

it('ignores non-mandatory requirements for eligibility', function () {
    InternshipRequirement::create([
        'name' => 'Portfolio',
        'type' => 'document',
        'is_mandatory' => false,
        'is_active' => true,
        'academic_year' => $this->academicYear,
    ]);

    // Should be eligible even without submitting non-mandatory requirements
    expect($this->placementService->isEligibleForPlacement($this->registration->id))->toBeTrue();
});

it('can fetch only eligible registrations for bulk matching', function () {
    // Create another registration
    $student2 = User::factory()->create()->assignRole('student');
    $reg2 = InternshipRegistration::create([
        'internship_id' => $this->internship->id,
        'student_id' => $student2->id,
        'academic_year' => $this->academicYear,
    ]);

    $requirement = InternshipRequirement::create([
        'name' => 'Consent Form',
        'type' => 'condition',
        'is_mandatory' => true,
        'academic_year' => $this->academicYear,
    ]);

    // Verify only the first registration
    RequirementSubmission::create([
        'registration_id' => $this->registration->id,
        'requirement_id' => $requirement->id,
        'status' => 'verified',
    ]);

    $eligible = $this->placementService->getEligibleRegistrations($this->academicYear);

    expect($eligible)->toHaveCount(1);
    expect($eligible->first()->id)->toBe($this->registration->id);
});

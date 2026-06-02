<?php

declare(strict_types=1);

use App\Domain\User\Enums\BloodType;
use App\Domain\User\Enums\EmploymentStatus;
use App\Domain\User\Enums\Gender;
use App\Domain\User\Models\Profile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('Profile', function () {
    it('casts gender to Gender enum', function () {
        $profile = Profile::factory()->male()->create();

        expect($profile->gender)->toBeInstanceOf(Gender::class);
        expect($profile->gender)->toBe(Gender::MALE);
    });

    it('casts blood_type to BloodType enum', function () {
        $profile = Profile::factory()->create(['blood_type' => BloodType::A]);

        expect($profile->blood_type)->toBeInstanceOf(BloodType::class);
        expect($profile->blood_type)->toBe(BloodType::A);
    });

    it('casts employment_status to EmploymentStatus enum when set', function () {
        $profile = Profile::factory()->create(['employment_status' => EmploymentStatus::FULL_TIME]);

        expect($profile->employment_status)->toBeInstanceOf(EmploymentStatus::class);
        expect($profile->employment_status)->toBe(EmploymentStatus::FULL_TIME);
    });

    it('allows null employment_status', function () {
        $profile = Profile::factory()->create(['employment_status' => null]);

        expect($profile->employment_status)->toBeNull();
    });
});

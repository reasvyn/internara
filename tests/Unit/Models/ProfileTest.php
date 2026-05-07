<?php

declare(strict_types=1);

use App\Enums\User\BloodType;
use App\Enums\User\Gender;
use App\Models\Department;
use App\Models\Profile;
use App\Models\User;
use Database\Factories\DepartmentFactory;
use Database\Factories\ProfileFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $profile = ProfileFactory::new()->create();

    expect($profile)->toBeInstanceOf(Profile::class)
        ->and($profile->id)->toBeUuid();
});

it('has uuid as primary key', function () {
    $profile = ProfileFactory::new()->create();

    expect($profile->id)->toBeUuid();
});

it('casts attributes correctly', function () {
    $profile = ProfileFactory::new()->create([
        'gender' => Gender::MALE,
        'blood_type' => BloodType::A,
    ]);

    expect($profile->gender)->toBe(Gender::MALE)
        ->and($profile->blood_type)->toBe(BloodType::A);
});

it('belongs to user', function () {
    $user = UserFactory::new()->create();
    $profile = ProfileFactory::new()->create(['user_id' => $user->id]);

    expect($profile->user)->toBeInstanceOf(User::class)
        ->and($profile->user->id)->toBe($user->id);
});

it('belongs to department', function () {
    $department = DepartmentFactory::new()->create();
    $profile = ProfileFactory::new()->create(['department_id' => $department->id]);

    expect($profile->department)->toBeInstanceOf(Department::class)
        ->and($profile->department->id)->toBe($department->id);
});

it('can create profile for student', function () {
    $profile = ProfileFactory::new()->forStudent()->create();

    expect($profile->registration_number)->toStartWith('STD-')
        ->and($profile->national_identifier)->not->toBeNull();
});

it('can create profile for teacher', function () {
    $profile = ProfileFactory::new()->forTeacher()->create();

    expect($profile->registration_number)->toStartWith('NIP-')
        ->and($profile->national_identifier)->toBeNull();
});

it('can create profile for supervisor', function () {
    $profile = ProfileFactory::new()->forSupervisor()->create();

    expect($profile->registration_number)->toBeNull()
        ->and($profile->national_identifier)->toBeNull()
        ->and($profile->department_id)->toBeNull();
});

it('can create male profile', function () {
    $profile = ProfileFactory::new()->male()->create();

    expect($profile->gender)->toBe(Gender::MALE);
});

it('can create female profile', function () {
    $profile = ProfileFactory::new()->female()->create();

    expect($profile->gender)->toBe(Gender::FEMALE);
});

it('has fillable attributes', function () {
    $user = UserFactory::new()->create();
    $department = DepartmentFactory::new()->create();

    $profile = Profile::create([
        'user_id' => $user->id,
        'phone' => '081234567890',
        'address' => 'Test Address',
        'gender' => Gender::MALE,
        'blood_type' => BloodType::O,
        'emergency_contact_name' => 'Emergency Contact',
        'emergency_contact_phone' => '089876543210',
        'emergency_contact_address' => 'Emergency Address',
        'bio' => 'Test bio',
        'national_identifier' => '123456789012',
        'registration_number' => 'REG-12345',
        'department_id' => $department->id,
    ]);

    expect($profile->phone)->toBe('081234567890')
        ->and($profile->address)->toBe('Test Address')
        ->and($profile->gender)->toBe(Gender::MALE)
        ->and($profile->blood_type)->toBe(BloodType::O)
        ->and($profile->emergency_contact_name)->toBe('Emergency Contact')
        ->and($profile->bio)->toBe('Test bio');
});

it('user has one profile relationship', function () {
    $user = UserFactory::new()->create();
    $profile = ProfileFactory::new()->create(['user_id' => $user->id]);

    $user->refresh();

    expect($user->profile)->toBeInstanceOf(Profile::class)
        ->and($user->profile->id)->toBe($profile->id);
});

<?php

declare(strict_types=1);

use Modules\Student\Models\Student;
use Modules\Support\Services\Contracts\OnboardingService;
use Modules\Teacher\Models\Teacher;
use Modules\User\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Setup roles
    \Modules\Permission\Models\Role::create(['name' => 'student']);
    \Modules\Permission\Models\Role::create(['name' => 'teacher']);
    \Modules\Permission\Models\Role::create(['name' => 'mentor']);
});

test('it can import students from CSV', function () {
    $csvContent = "name,email,nisn,phone\n";
    $csvContent .= "John Doe,john@example.com,1234567890,08123456789\n";
    $csvContent .= "Jane Doe,jane@example.com,0987654321,08987654321\n";

    $filePath = tempnam(sys_get_temp_dir(), 'import_').'.csv';
    file_put_contents($filePath, $csvContent);

    $service = app(OnboardingService::class);
    $results = $service->importFromCsv($filePath, 'student');

    expect($results['success'])->toBe(2)
        ->and($results['failure'])->toBe(0);

    $this->assertDatabaseHas('users', ['email' => 'john@example.com', 'name' => 'John Doe']);
    $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'name' => 'Jane Doe']);

    $john = User::where('email', 'john@example.com')->first();
    expect($john->hasRole('student'))->toBeTrue();
    expect($john->profile->profileable)->toBeInstanceOf(Student::class);
    expect($john->profile->profileable->nisn)->toBe('1234567890');

    unlink($filePath);
});

test('it can import teachers from CSV', function () {
    $csvContent = "name,email,nip\n";
    $csvContent .= "Teacher One,teacher1@example.com,19900101\n";

    $filePath = tempnam(sys_get_temp_dir(), 'import_').'.csv';
    file_put_contents($filePath, $csvContent);

    $service = app(OnboardingService::class);
    $results = $service->importFromCsv($filePath, 'teacher');

    expect($results['success'])->toBe(1);

    $teacher = User::where('email', 'teacher1@example.com')->first();
    expect($teacher->hasRole('teacher'))->toBeTrue();
    expect($teacher->profile->profileable)->toBeInstanceOf(Teacher::class);
    expect($teacher->profile->profileable->nip)->toBe('19900101');

    unlink($filePath);
});

test('it handles validation errors in CSV rows', function () {
    $csvContent = "name,email\n";
    $csvContent .= ",missing@email.com\n"; // Missing name
    $csvContent .= "Invalid Email,not-an-email\n";

    $filePath = tempnam(sys_get_temp_dir(), 'import_').'.csv';
    file_put_contents($filePath, $csvContent);

    $service = app(OnboardingService::class);
    $results = $service->importFromCsv($filePath, 'student');

    expect($results['failure'])->toBe(2);
    expect($results['errors'])->toHaveCount(2);

    unlink($filePath);
});

test('it can handle a larger batch of student imports', function () {
    $count = 20;
    $csvContent = "name,email,nisn\n";
    for ($i = 1; $i <= $count; $i++) {
        $csvContent .= "Student {$i},student{$i}@example.com,nisn{$i}\n";
    }

    $filePath = tempnam(sys_get_temp_dir(), 'import_bulk_').'.csv';
    file_put_contents($filePath, $csvContent);

    $service = app(OnboardingService::class);
    $results = $service->importFromCsv($filePath, 'student');

    expect($results['success'])->toBe($count);
    $this->assertDatabaseCount('users', $count);

    // Verify one of them
    $student = User::where('email', 'student10@example.com')->first();
    expect($student->name)->toBe('Student 10');

    unlink($filePath);
});

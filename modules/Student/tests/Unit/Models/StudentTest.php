<?php

declare(strict_types=1);

namespace Modules\Student\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Student\Models\Student;

uses(RefreshDatabase::class);

test('student model has the expected fields and encryption', function () {
    $student = Student::factory()->create([
        'registration_number' => '12345678',
        'national_identifier' => '0012345678',
        'class_name' => 'XII RPL 1',
    ]);

    expect($student->registration_number)
        ->toBe('12345678')
        ->and($student->national_identifier)
        ->toBe('0012345678')
        ->and($student->class_name)
        ->toBe('XII RPL 1');

    // Verify encryption in database
    $raw = \DB::table('students')->where('id', $student->id)->first();
    expect($raw->registration_number)
        ->not->toBe('12345678')
        ->and($raw->national_identifier)
        ->not->toBe('0012345678');
});

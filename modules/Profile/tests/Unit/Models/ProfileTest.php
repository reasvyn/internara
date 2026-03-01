<?php

declare(strict_types=1);

namespace Modules\Profile\Tests\Unit\Models;


use Modules\Profile\Models\Profile;



test('profile model has the expected fields and encryption', function () {
    $profile = Profile::factory()->create([
        'phone' => '081234567890',
        'address' => 'Jl. Merdeka No. 1',
        'gender' => 'male',
        'blood_type' => 'O',
        'emergency_contact_name' => 'Wali Siswa',
        'emergency_contact_phone' => '08987654321',
        'emergency_contact_address' => 'Jl. Keadilan No. 2',
    ]);

    expect($profile->phone)
        ->toBe('081234567890')
        ->and($profile->address)
        ->toBe('Jl. Merdeka No. 1')
        ->and($profile->gender)
        ->toBe('male')
        ->and($profile->blood_type)
        ->toBe('O')
        ->and($profile->emergency_contact_name)
        ->toBe('Wali Siswa')
        ->and($profile->emergency_contact_phone)
        ->toBe('08987654321')
        ->and($profile->emergency_contact_address)
        ->toBe('Jl. Keadilan No. 2');

    $raw = \DB::table('profiles')->where('id', $profile->id)->first();
    expect($raw->phone)
        ->not->toBe('081234567890')
        ->and($raw->address)
        ->not->toBe('Jl. Merdeka No. 1')
        ->and($raw->emergency_contact_phone)
        ->not->toBe('08987654321')
        ->and($raw->emergency_contact_address)
        ->not->toBe('Jl. Keadilan No. 2');
});

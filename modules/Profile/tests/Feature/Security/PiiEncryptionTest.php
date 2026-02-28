<?php

declare(strict_types=1);

namespace Modules\Profile\Tests\Feature\Security;

use Illuminate\Support\Facades\DB;
use Modules\Profile\Models\Profile;
use Modules\User\Models\User;

describe('PII Encryption Security Test (BP-ID-01)', function () {
    
    test('sensitive profile fields are stored as ciphertext in the database', function () {
        $user = User::factory()->create();
        
        $phone = '08123456789';
        $address = 'Jl. Merdeka No. 45, Jakarta';
        $nationalId = '1234567890123456';
        
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'phone' => $phone,
            'address' => $address,
            'national_identifier' => $nationalId,
        ]);

        // Retrieve raw value directly from DB to verify encryption
        $rawProfile = DB::table('profiles')->where('id', $profile->id)->first();

        // Values in DB should NOT match the original text (they should be encrypted)
        expect($rawProfile->phone)->not->toBe($phone);
        expect($rawProfile->address)->not->toBe($address);
        expect($rawProfile->national_identifier)->not->toBe($nationalId);
        
        // Ensure they look like encrypted strings (Laravel typically starts with base64 encoded JSON)
        expect(base64_decode($rawProfile->phone, true))->not->toBeFalse();
    });

    test('sensitive profile fields are automatically decrypted during hydration', function () {
        $user = User::factory()->create();
        $phone = '08123456789';
        
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'phone' => $phone,
        ]);

        // Re-fetch the model
        $refetchedProfile = Profile::find($profile->id);

        // Value should be decrypted automatically by Eloquent
        expect($refetchedProfile->phone)->toBe($phone);
    });
});

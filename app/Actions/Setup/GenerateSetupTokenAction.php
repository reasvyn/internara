<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Models\Setup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

final class GenerateSetupTokenAction
{
    /**
     * @return array{plaintext: string, expires_at: Carbon}
     */
    public function execute(): array
    {
        $length = (int) config('setup.token.length', 64);
        $expiryMinutes = (int) config('setup.token.expiry_minutes', 60);

        $plaintext = Str::random($length);
        $encrypted = Crypt::encryptString($plaintext);
        $expiresAt = now()->addMinutes($expiryMinutes);

        $setup = Setup::firstOrCreate([]);
        $setup->forceFill([
            'setup_token' => $encrypted,
            'token_expires_at' => $expiresAt,
        ])->save();

        return [
            'plaintext' => $plaintext,
            'expires_at' => $expiresAt,
        ];
    }
}

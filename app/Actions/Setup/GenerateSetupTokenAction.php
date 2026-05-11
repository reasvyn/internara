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
        $plaintext = Str::random(64);
        $encrypted = Crypt::encryptString($plaintext);
        $expiresAt = now()->addHour();

        $setup = Setup::firstOrCreate([]);
        $setup->update([
            'setup_token' => $encrypted,
            'token_expires_at' => $expiresAt,
        ]);

        return [
            'plaintext' => $plaintext,
            'expires_at' => $expiresAt,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Setup\Installation\Actions;

use App\Core\Actions\BaseAction;
use App\Setup\Entities\SetupEntity;
use App\Setup\Installation\Data\SetupTokenData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

final class GenerateSetupTokenAction extends BaseAction
{
    public function execute(): SetupTokenData
    {
        return Cache::lock('setup.token.generation', 10)->block(15, function () {
            return $this->transaction(function () {
                $length = (int) config('setup.token.length', 64);
                $expiryMinutes = (int) config('setup.token.expiry_minutes', 60);

                $plaintext = Str::random($length);
                $encrypted = Crypt::encryptString($plaintext);
                $expiresAt = now()->addMinutes($expiryMinutes);

                $state = SetupEntity::get();
                $version = $state->tokenVersion() + 1;

                SetupEntity::update([
                    'install_token' => $encrypted,
                    'token_expires_at' => $expiresAt->toIso8601String(),
                    'token_version' => $version,
                    'updated_at' => now()->toIso8601String(),
                ]);

                return new SetupTokenData(plaintext: $plaintext, expiresAt: $expiresAt);
            });
        });
    }
}

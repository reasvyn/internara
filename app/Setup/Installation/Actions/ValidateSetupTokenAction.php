<?php

declare(strict_types=1);

namespace App\Setup\Installation\Actions;

use App\Core\Actions\BaseAction;
use App\Setup\Entities\SetupEntity;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

final class ValidateSetupTokenAction extends BaseAction
{
    public function execute(string $token): void
    {
        $this->transaction(function () use ($token) {
            $state = SetupEntity::get();

            if (! $state->hasStoredToken()) {
                throw new RuntimeException('Setup token is missing from the system.');
            }

            if ($state->isTokenExpired(now())) {
                throw new RuntimeException('Setup token has expired.');
            }

            $storedToken = $state->setupToken();

            if ($storedToken === null) {
                throw new RuntimeException('Setup token is missing from the system.');
            }

            try {
                $decrypted = Crypt::decryptString($storedToken);
            } catch (\Throwable) {
                throw new RuntimeException('Setup token is malformed or corrupted.');
            }

            if (! hash_equals($decrypted, $token)) {
                throw new RuntimeException('The provided setup token does not match.');
            }

            SetupEntity::update([
                'install_token' => null,
                'token_expires_at' => null,
                'updated_at' => now()->toIso8601String(),
            ]);
        });
    }
}

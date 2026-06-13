<?php

declare(strict_types=1);

namespace App\Setup\Installation\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Setup\Entities\SetupEntity;
use Illuminate\Support\Facades\Crypt;

final class ValidateSetupTokenAction extends BaseAction
{
    public function execute(string $token): void
    {
        $this->transaction(function () use ($token) {
            $state = SetupEntity::get();

            if (! $state->hasStoredToken()) {
                throw new RejectedException('Setup token is missing from the system.');
            }

            if ($state->isTokenExpired(now())) {
                throw new RejectedException('Setup token has expired.');
            }

            try {
                $decrypted = Crypt::decryptString($state->setupToken());
            } catch (\Throwable) {
                throw new RejectedException('Setup token is malformed or corrupted.');
            }

            if (! hash_equals($decrypted, $token)) {
                throw new RejectedException('The provided setup token does not match.');
            }

            SetupEntity::update([
                'install_token' => null,
                'token_expires_at' => null,
                'updated_at' => now()->toIso8601String(),
            ]);
        });
    }
}

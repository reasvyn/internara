<?php

declare(strict_types=1);

namespace App\Setup\Actions;

use App\Core\Actions\BaseAction;
use App\Settings\Support\Settings;
use App\Setup\Entities\SetupState;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

final class ValidateSetupTokenAction extends BaseAction
{
    public function execute(string $token): void
    {
        $this->transaction(function () use ($token) {
            $state = SetupState::fromSettings();

            if (! $state->hasStoredToken()) {
                throw new RuntimeException('Setup token is missing from the system.');
            }

            if ($state->isTokenExpired(now())) {
                throw new RuntimeException('Setup token has expired.');
            }

            $storedToken = Settings::get('setup.install_token');

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

            Settings::set([
                'setup.install_token' => ['value' => null, 'group' => 'setup', 'type' => 'string'],
                'setup.token_expires_at' => ['value' => null, 'group' => 'setup', 'type' => 'datetime'],
                'setup.updated_at' => ['value' => now()->toIso8601String(), 'group' => 'setup', 'type' => 'datetime'],
            ]);
        });
    }
}

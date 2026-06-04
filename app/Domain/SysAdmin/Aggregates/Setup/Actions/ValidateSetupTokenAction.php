<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\Setup\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\SysAdmin\Aggregates\Setup\Models\Setup;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

final class ValidateSetupTokenAction extends BaseAction
{
    public function execute(string $token): void
    {
        $this->transaction(function () use ($token) {
            $setup = Setup::lockForUpdate()->latest('created_at')->first();

            if (! $setup) {
                throw new RuntimeException('No setup configuration found. Run php artisan setup:install first.');
            }

            $state = $setup->asSetupState();

            if (! $state->hasStoredToken()) {
                throw new RuntimeException('Setup token is missing from the system.');
            }

            if ($state->isTokenExpired(now())) {
                throw new RuntimeException('Setup token has expired.');
            }

            try {
                $decrypted = Crypt::decryptString($setup->setup_token);
            } catch (\Throwable) {
                throw new RuntimeException('Setup token is malformed or corrupted.');
            }

            if (! hash_equals($decrypted, $token)) {
                throw new RuntimeException('The provided setup token does not match.');
            }

            $setup->fill([
                'setup_token' => null,
                'token_expires_at' => null,
            ])->save();
        });
    }
}

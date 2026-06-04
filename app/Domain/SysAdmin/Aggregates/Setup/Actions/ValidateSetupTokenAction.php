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
                throw new RuntimeException('Invalid setup token.');
            }

            $state = $setup->asSetupState();

            if (! $state->hasStoredToken() || $state->isTokenExpired(now())) {
                throw new RuntimeException('Invalid setup token.');
            }

            try {
                $decrypted = Crypt::decryptString($setup->setup_token);
            } catch (\Throwable) {
                throw new RuntimeException('Invalid setup token.');
            }

            if (! hash_equals($decrypted, $token)) {
                throw new RuntimeException('Invalid setup token.');
            }

            $setup->fill([
                'setup_token' => null,
                'token_expires_at' => null,
            ])->save();
        });
    }
}

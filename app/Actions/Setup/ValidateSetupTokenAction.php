<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Models\Setup;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

final class ValidateSetupTokenAction
{
    public function execute(string $token): void
    {
        $setup = Setup::latest('created_at')->first();

        if (! $setup) {
            throw new RuntimeException('Invalid setup token.');
        }

        $state = Setup::state();

        if (! $state->hasStoredToken() || $state->isTokenExpired(now())) {
            throw new RuntimeException('Invalid setup token.');
        }

        try {
            $decrypted = Crypt::decryptString($setup->setup_token);
        } catch (\Exception) {
            throw new RuntimeException('Invalid setup token.');
        }

        if (! hash_equals($decrypted, $token)) {
            throw new RuntimeException('Invalid setup token.');
        }
    }
}

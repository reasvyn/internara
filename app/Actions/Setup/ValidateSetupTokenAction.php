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
        $state = Setup::state();

        if ($state->setupToken === null) {
            throw new RuntimeException('Invalid setup token.');
        }

        try {
            $decrypted = Crypt::decryptString($state->setupToken);
        } catch (\Exception) {
            throw new RuntimeException('Invalid setup token.');
        }

        if (! $state->validateToken($decrypted, $token, now())) {
            throw new RuntimeException('Invalid setup token.');
        }
    }
}

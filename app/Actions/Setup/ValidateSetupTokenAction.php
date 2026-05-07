<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Models\Setup;
use RuntimeException;

final class ValidateSetupTokenAction
{
    public function execute(string $token): void
    {
        if (! Setup::validateToken($token)) {
            throw new RuntimeException('Invalid setup token.');
        }
    }
}

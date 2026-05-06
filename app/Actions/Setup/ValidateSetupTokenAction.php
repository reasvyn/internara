<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Domain\Setup\Exceptions\SetupException;
use App\Models\Setup;

final class ValidateSetupTokenAction
{
    public function execute(string $token): void
    {
        if (! Setup::validateToken($token)) {
            throw SetupException::invalidToken();
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use App\Models\Setup;
use Illuminate\Support\Carbon;

final class GenerateSetupTokenAction
{
    /**
     * @return array{plaintext: string, expires_at: Carbon}
     */
    public function execute(): array
    {
        $tokenData = Setup::generateToken();

        return [
            'plaintext' => $tokenData['plaintext'],
            'expires_at' => $tokenData['expires_at'],
        ];
    }
}

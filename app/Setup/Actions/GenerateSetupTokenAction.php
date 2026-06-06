<?php

declare(strict_types=1);

namespace App\Setup\Actions;

use App\Core\Actions\BaseAction;
use App\Settings\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

final class GenerateSetupTokenAction extends BaseAction
{
    /**
     * @return array{plaintext: string, expires_at: Carbon}
     */
    public function execute(): array
    {
        return Cache::lock('setup.token.generation', 10)->block(15, function () {
            return $this->transaction(function () {
                $length = (int) config('setup.token.length', 64);
                $expiryMinutes = (int) config('setup.token.expiry_minutes', 60);

                $plaintext = Str::random($length);
                $encrypted = Crypt::encryptString($plaintext);
                $expiresAt = now()->addMinutes($expiryMinutes);
                $version = (int) Settings::get('setup.token_version', 0) + 1;

                Settings::set([
                    'setup.install_token' => ['value' => $encrypted, 'group' => 'setup', 'type' => 'string'],
                    'setup.token_expires_at' => ['value' => $expiresAt->toIso8601String(), 'group' => 'setup', 'type' => 'datetime'],
                    'setup.token_version' => ['value' => $version, 'group' => 'setup', 'type' => 'integer'],
                    'setup.updated_at' => ['value' => now()->toIso8601String(), 'group' => 'setup', 'type' => 'datetime'],
                ]);

                return [
                    'plaintext' => $plaintext,
                    'expires_at' => $expiresAt,
                ];
            });
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Setup\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\CacheKeys;
use App\Domain\Setup\Models\Setup;
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

                $setup = Setup::firstOrCreate([]);
                $setup->fill([
                    'setup_token' => $encrypted,
                    'token_expires_at' => $expiresAt,
                ])->save();

                Cache::forget(CacheKeys::SETUP_INSTALLED);

                return [
                    'plaintext' => $plaintext,
                    'expires_at' => $expiresAt,
                ];
            });
        });
    }
}

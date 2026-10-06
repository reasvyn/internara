<?php

declare(strict_types=1);

namespace App\Modules\Setup\Domain\Installation\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Support\Token;
use App\Modules\Setting\Actions\BatchSetSettingAction;
use App\Modules\Setup\Domain\Installation\Data\SetupTokenData;
use App\Modules\Setup\Entities\SetupEntity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

final class GenerateSetupTokenAction extends BaseCommandAction
{
    public function __construct(protected readonly BatchSetSettingAction $batchSetSetting) {}

    public function execute(): SetupTokenData
    {
        $lockKey = Config::get('cache-keys.setup_token_generation', 'setup.token.generation');

        return Cache::lock($lockKey, 10)->block(15, function () {
            return $this->transaction(function () {
                $length = (int) config('setup.token.length', 6);
                $charset = (string) config('setup.token.charset', Token::UPPERCASE_ALNUM);
                $expiryMinutes = (int) config('setup.token.expiry_minutes', 60);

                $plaintext = Token::generate($length, $charset);
                $encrypted = Crypt::encryptString($plaintext);
                $expiresAt = now()->addMinutes($expiryMinutes);

                $state = SetupEntity::get();
                $version = $state->tokenVersion() + 1;

                $this->batchSetSetting->execute(
                    ...SetupEntity::toSettingsEntries([
                        'install_token' => $encrypted,
                        'token_expires_at' => $expiresAt->toIso8601String(),
                        'token_version' => $version,
                        'updated_at' => now()->toIso8601String(),
                    ]),
                );

                $this->log('setup_token_generated', null, [
                    'token_version' => $version,
                    'expires_at' => $expiresAt->toIso8601String(),
                ]);

                return new SetupTokenData(plaintext: $plaintext, expiresAt: $expiresAt);
            });
        });
    }
}

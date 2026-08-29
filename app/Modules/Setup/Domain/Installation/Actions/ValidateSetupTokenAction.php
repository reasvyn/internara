<?php

declare(strict_types=1);

namespace App\Modules\Setup\Domain\Installation\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Settings\Actions\BatchSetSettingAction;
use App\Modules\Setup\Entities\SetupEntity;
use Illuminate\Support\Facades\Crypt;

final class ValidateSetupTokenAction extends BaseCommandAction
{
    public function __construct(protected readonly BatchSetSettingAction $batchSetSetting) {}

    public function execute(string $token): void
    {
        $this->transaction(function () use ($token) {
            $state = SetupEntity::get();

            if (! $state->hasStoredToken()) {
                throw new RejectedException(__('setup.token_missing'));
            }

            if ($state->isTokenExpired(now())) {
                throw new RejectedException(__('setup.token_expired'));
            }

            try {
                $decrypted = Crypt::decryptString($state->setupToken());
            } catch (\Throwable) {
                throw new RejectedException(__('setup.token_malformed'));
            }

            if (! hash_equals($decrypted, $token)) {
                throw new RejectedException(__('setup.token_mismatch'));
            }

            $this->batchSetSetting->execute(
                ...SetupEntity::toSettingsEntries([
                    'install_token' => null,
                    'token_expires_at' => null,
                    'updated_at' => now()->toIso8601String(),
                ]),
            );

            $this->log('setup_token_validated', null, [
                'token_version' => $state->tokenVersion(),
            ]);
        });
    }
}

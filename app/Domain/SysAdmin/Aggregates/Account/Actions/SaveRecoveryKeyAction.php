<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\Account\Actions;

use App\Domain\Core\Actions\BaseAction;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SaveRecoveryKeyAction extends BaseAction
{
    public function execute(string $plaintext): string
    {
        return $this->withErrorHandling(function () use ($plaintext) {
            $dir = storage_path('app/private');

            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $path = "{$dir}/.recovery-key";

            $header = '# INTERNARA RECOVERY KEY'.PHP_EOL
                .'# This key grants super admin access. Keep it secret, keep it safe.'.PHP_EOL
                .'# Only the server owner can read this file.'.PHP_EOL
                .'# Generated: '.now()->toIso8601String().PHP_EOL
                .PHP_EOL
                .$plaintext.PHP_EOL;

            if (File::put($path, $header) === false) {
                throw new RuntimeException(sprintf('Failed to write recovery key to [%s]', $path));
            }

            File::chmod($path, 0600);

            $this->log('recovery_key_saved');

            return $path;
        }, 'Failed to save recovery key');
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Exceptions\InfrastructureException;

final class AppIntegrity
{
    private const AUTHOR_NAME = 'Reas Vyn';

    public static function verify(): void
    {
        try {
            self::verifyComposerMetadata();
        } catch (InfrastructureException $e) {
            if (app()->environment('local', 'testing')) {
                try {
                    SmartLogger::warning($e->getMessage())->withPiiMasking()->systemOnly()->save();
                } catch (\Throwable) {
                    error_log('[AppIntegrity] '.$e->getMessage());
                }

                return;
            }

            throw $e;
        }
    }

    private static function verifyComposerMetadata(): void
    {
        $path = base_path('composer.json');

        if (! file_exists($path)) {
            throw new InfrastructureException(
                'Core system metadata (composer.json) is missing.',
                hint: 'Verify the application installation is complete.',
            );
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new InfrastructureException(
                'Failed to read core system metadata file.',
                hint: 'Check file permissions for composer.json.',
            );
        }

        $info = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InfrastructureException(
                'Core system metadata file contains invalid JSON: '.json_last_error_msg(),
                hint: 'Validate composer.json syntax.',
            );
        }

        $info = is_array($info) ? $info : [];
        $authorName = $info['authors'][0]['name'] ?? '';

        if (! hash_equals(self::AUTHOR_NAME, $authorName)) {
            throw new InfrastructureException(
                'Attribution Error: Unauthorized author modification detected. '.
                    'This system requires attribution to the original author.',
                hint: 'Restore the original author name in composer.json.',
            );
        }
    }
}

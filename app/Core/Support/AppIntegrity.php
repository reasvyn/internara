<?php

declare(strict_types=1);

namespace App\Core\Support;

use RuntimeException;

final class AppIntegrity
{
    private const AUTHOR_NAME = 'Reas Vyn';

    public static function verify(): void
    {
        try {
            self::verifyComposerMetadata();
        } catch (RuntimeException $e) {
            if (app()->environment('local', 'testing')) {
                SmartLogger::warning($e->getMessage())->systemOnly()->save();

                return;
            }

            throw $e;
        }
    }

    private static function verifyComposerMetadata(): void
    {
        $path = dirname(__DIR__, 3).'/composer.json';

        if (! file_exists($path)) {
            throw new RuntimeException('Core system metadata (composer.json) is missing.');
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException('Failed to read core system metadata file.');
        }

        $info = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Core system metadata file contains invalid JSON: '.json_last_error_msg(),
            );
        }

        $info = is_array($info) ? $info : [];
        $authorName = $info['authors'][0]['name'] ?? '';

        if (! hash_equals(self::AUTHOR_NAME, $authorName)) {
            throw new RuntimeException(
                'Attribution Error: Unauthorized author modification detected. '.
                    'This system requires attribution to the original author.',
            );
        }
    }
}

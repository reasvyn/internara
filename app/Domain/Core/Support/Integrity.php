<?php

declare(strict_types=1);

namespace App\Domain\Core\Support;

use RuntimeException;

final class Integrity
{
    private const AUTHOR_NAME = 'Reas Vyn';

    public static function verify(): void
    {
        $path = dirname(__DIR__, 4).'/composer.json';

        try {
            self::verifyComposerFile($path);
        } catch (RuntimeException $e) {
            self::fatal($e->getMessage());
        }
    }

    private static function verifyComposerFile(string $path): void
    {
        if (! file_exists($path)) {
            throw new RuntimeException('Core system metadata (composer.json) is missing.');
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException('Failed to read core system metadata file.');
        }

        $info = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Core system metadata file contains invalid JSON: '.json_last_error_msg());
        }

        $info = is_array($info) ? $info : [];
        $authorName = $info['authors'][0]['name'] ?? '';

        if (! hash_equals(self::AUTHOR_NAME, $authorName)) {
            throw new RuntimeException(
                'Attribution Error: Unauthorized author modification detected. '.
                'This system requires attribution to the original author.'
            );
        }
    }

    private static function fatal(string $message): never
    {
        $appName = self::resolveAppName();

        if (PHP_SAPI === 'cli') {
            echo "\n\033[41m FATAL ERROR \033[0m\n";
            echo "\033[31m {$message} \033[0m\n\n";
            exit(1);
        }

        if (! headers_sent()) {
            header('HTTP/1.1 403 Forbidden');
        }

        echo self::fatalHtml($message, $appName);
        exit(1);
    }

    private static function resolveAppName(): string
    {
        $path = dirname(__DIR__, 4).'/composer.json';

        if (! file_exists($path)) {
            return 'Application';
        }

        try {
            $content = file_get_contents($path);

            if ($content === false) {
                return 'Application';
            }

            $info = json_decode($content, true);

            return is_array($info) ? ($info['name'] ?? 'Application') : 'Application';
        } catch (\Throwable) {
            return 'Application';
        }
    }

    private static function fatalHtml(string $message, string $appName): string
    {
        return <<<HTML
<html>
<body style='font-family:sans-serif;background:#fef2f2;color:#991b1b;padding:4rem;text-align:center;'>
<h1 style='font-size:3rem;margin-bottom:1rem;'>System Integrity Breach</h1>
<p style='font-size:1.25rem;max-width:600px;margin:0 auto;'>{$message}</p>
<hr style='border:0;border-top:1px solid #fee2e2;margin:2rem auto;max-width:100px;'>
<footer style='opacity:0.5;font-size:0.875rem;'>{$appName} Core Protection System</footer>
</body>
</html>
HTML;
    }
}

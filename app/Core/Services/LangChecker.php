<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Support\SmartLogger;
use Illuminate\Translation\Translator;

final class LangChecker extends Translator
{
    public function get($key, array $replace = [], $locale = null, $fallback = true): string|array
    {
        $result = parent::get($key, $replace, $locale, $fallback);

        if (is_string($result) && $result === $key) {
            $caller = $this->findCaller();

            SmartLogger::warning("Missing translation key: {$key}")
                ->withPayload([
                    'locale' => $locale ?: $this->locale,
                    'called_in' => $caller['file'] ?? 'unknown',
                    'called_at_line' => $caller['line'] ?? 0,
                ])
                ->withPiiMasking()
                ->systemOnly()
                ->save();
        }

        return $result;
    }

    private function findCaller(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';

            if (
                $file === '' ||
                str_contains($file, '/vendor/') ||
                str_contains($file, '/Services/LangChecker') ||
                str_contains($file, '/Translation/')
            ) {
                continue;
            }

            return [
                'file' => str_replace(base_path(), '', $file),
                'line' => $frame['line'] ?? 0,
            ];
        }

        return [];
    }
}

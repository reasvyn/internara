<?php

declare(strict_types=1);

namespace Modules\Log\Logging;

use Modules\Shared\Support\Masker;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Monolog processor to automatically mask PII in log messages and context.
 */
class PiiMaskingProcessor implements ProcessorInterface
{
    /**
     * Fields that should be masked.
     */
    protected array $sensitiveFields = [
        'email',
        'password',
        'password_confirmation',
        'phone',
        'address',
        'nip',
        'nisn',
        'token',
        'secret',
    ];

    /**
     * {@inheritdoc}
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;

        if (! empty($context)) {
            $record = $record->with(context: $this->maskArray($context));
        }

        return $record;
    }

    /**
     * Recursively mask sensitive fields in an array.
     */
    protected function maskArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskArray($value);
            } elseif (in_array(strtolower((string) $key), $this->sensitiveFields)) {
                $data[$key] = $this->maskValue((string) $key, (string) $value);
            }
        }

        return $data;
    }

    /**
     * Mask a specific value based on its field name.
     */
    protected function maskValue(string $key, string $value): string
    {
        if (str_contains(strtolower($key), 'email')) {
            return Masker::email($value);
        }

        return Masker::sensitive($value);
    }
}

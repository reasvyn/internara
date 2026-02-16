<?php

declare(strict_types=1);

namespace Modules\Exception;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class RecordNotFoundException.
 *
 * A specialized exception for scenarios where a requested data record
 * or resource cannot be located in the system.
 */
class RecordNotFoundException extends AppException
{
    /**
     * Create a new RecordNotFoundException instance.
     *
     * @param string $userMessage The translation key for the user-friendly message (e.g., "records::exception.not_found").
     * @param array $replace Parameters to pass to the translator for replacement.
     * @param string|null $locale Specific locale to use for the user message.
     * @param array $record The record identifier(s) that were not found, for logging context.
     * @param string $logMessage The log message to use for logging purposes.
     * @param int $code The HTTP status code, defaulting to 404 (Not Found).
     * @param Throwable|null $previous The previous exception used for chaining.
     */
    public function __construct(
        string $userMessage = 'exception::messages.not_found',
        array $replace = [],
        ?string $locale = null,
        array $record = [],
        string $logMessage = 'Record not found.',
        int $code = Response::HTTP_NOT_FOUND,
        ?Throwable $previous = null,
    ) {
        $context = [];

        if (!empty($record)) {
            $context['record'] = $record;
        }

        parent::__construct(
            userMessage: $userMessage,
            replace: $replace,
            locale: $locale,
            logMessage: $logMessage,
            code: $code,
            previous: $previous,
            context: $context,
        );
    }
}

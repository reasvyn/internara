<?php

declare(strict_types=1);

namespace Modules\Exception;

use DomainException;
use Illuminate\Support\Facades\Log;

/**
 * Standardized Exception for localized resource discovery failures.
 *
 * This exception is caught by the global handler and transformed into
 * a consistent, localized UI feedback.
 */
final class RecordNotFoundException extends DomainException
{
    /**
     * @param string|null $message Optional localized translation key.
     * @param string|null $uuid The UUID of the missing record.
     * @param string|null $module The module where the record was sought.
     * @param array $replace Replacement data for translation keys.
     * @param mixed $record Legacy record data for backward compatibility.
     */
    public function __construct(
        ?string $message = null,
        public ?string $uuid = null,
        public ?string $module = null,
        public array $replace = [],
        public mixed $record = null
    ) {
        $message = $message ?? __('exception::messages.record_not_found');
        parent::__construct($message, 404);

        $this->logDiscoveryFailure();
    }

    /**
     * Get the context for the exception.
     */
    public function getContext(): array
    {
        return [
            'uuid'    => $this->uuid,
            'module'  => $this->module,
            'record'  => $this->record ?? $this->replace['record'] ?? null,
            'replace' => $this->replace,
        ];
    }

    /**
     * Create a new instance for a specific UUID and module context.
     */
    public static function for(string $uuid, string $module, ?string $message = null): self
    {
        return new self($message, $uuid, $module);
    }

    /**
     * Log the discovery failure for forensic analysis.
     */
    protected function logDiscoveryFailure(): void
    {
        Log::warning("[RecordNotFound] Resource not found in module [{$this->module}] with UUID [{$this->uuid}]", [
            'uuid'   => $this->uuid,
            'module' => $this->module,
        ]);
    }
}

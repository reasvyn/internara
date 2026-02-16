<?php

declare(strict_types=1);

namespace Modules\Log\Logging;

use Monolog\Logger;

/**
 * Custom logger to apply PiiMaskingProcessor to Monolog.
 */
class CustomLogger
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(mixed $logger): void
    {
        $monolog = $logger instanceof \Illuminate\Log\Logger ? $logger->getLogger() : $logger;

        if ($monolog instanceof \Monolog\Logger) {
            $monolog->pushProcessor(new PiiMaskingProcessor());
        }
    }
}

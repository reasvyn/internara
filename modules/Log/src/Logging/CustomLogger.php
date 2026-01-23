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
     * Customize the given Monolog instance.
     */
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(new PiiMaskingProcessor);
    }
}

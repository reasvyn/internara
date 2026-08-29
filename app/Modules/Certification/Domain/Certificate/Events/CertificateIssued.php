<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Events;

use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Core\Events\BaseEvent;

final class CertificateIssued extends BaseEvent
{
    public function __construct(public Certificate $certificate) {}

    public function eventName(): string
    {
        return 'certificate.issued';
    }
}

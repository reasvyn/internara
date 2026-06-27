<?php

declare(strict_types=1);

namespace App\Certification\Certificate\Events;

use App\Certification\Certificate\Models\Certificate;
use App\Core\Events\BaseEvent;

final class CertificateIssued extends BaseEvent
{
    public function __construct(public Certificate $certificate) {}

    public function eventName(): string
    {
        return 'certificate.issued';
    }
}

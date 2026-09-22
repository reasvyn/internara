<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Data;

use App\Modules\Core\Data\BaseData;

final readonly class CertificateStatusView extends BaseData
{
    public function __construct(
        public string $status,
        public ?string $studentName = null,
        public ?string $schoolName = null,
        public ?string $certificateNumber = null,
        public ?string $issuedAt = null,
        public ?string $revokedAt = null,
    ) {}

    public static function notFound(): self
    {
        return new self(
            status: 'not_found',
        );
    }
}

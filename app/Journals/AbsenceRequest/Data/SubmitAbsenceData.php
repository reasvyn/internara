<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Data;

use App\Core\Data\BaseData;
use App\User\Models\User;

final readonly class SubmitAbsenceData extends BaseData
{
    public function __construct(
        public User $user,
        public string $registrationId,
        /** @var array{start_date?: string, reason_type: string, reason_description?: string, attachment_path?: string} */
        public array $data,
    ) {}
}

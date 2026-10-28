<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Data;

use App\Modules\Core\Data\BaseData;

final readonly class SubmitPartnerAssessmentData extends BaseData
{
    /**
     * @param array<string, array<string, float|int|string>> $scores
     */
    public function __construct(
        public string $token,
        public string $evaluatorName,
        public ?string $evaluatorPhone = null,
        public ?string $feedback = null,
        public array $scores = [],
    ) {}
}

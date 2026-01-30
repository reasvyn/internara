<?php

declare(strict_types=1);

namespace Modules\Mentor\Services;

use Modules\Mentor\Models\MentoringLog;
use Modules\Mentor\Models\MentoringVisit;
use Modules\Mentor\Services\Contracts\MentoringService as Contract;
use Modules\Shared\Services\EloquentQuery;

class MentoringService extends EloquentQuery implements Contract
{
    public function __construct(MentoringVisit $model)
    {
        $this->setModel($model);
    }

    /**
     * {@inheritdoc}
     */
    public function recordVisit(array $data): MentoringVisit
    {
        return MentoringVisit::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function recordLog(array $data): MentoringLog
    {
        return MentoringLog::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getMentoringStats(string $registrationId): array
    {
        return [
            'visits_count' => MentoringVisit::where('registration_id', $registrationId)->count(),
            'logs_count' => MentoringLog::where('registration_id', $registrationId)->count(),
            'last_visit' => MentoringVisit::where('registration_id', $registrationId)
                ->latest('visit_date')
                ->first(),
        ];
    }
}

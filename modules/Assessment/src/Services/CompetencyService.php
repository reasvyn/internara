<?php

declare(strict_types=1);

namespace Modules\Assessment\Services;

use Modules\Assessment\Models\Competency;
use Modules\Assessment\Models\DepartmentCompetency;
use Modules\Assessment\Models\StudentCompetencyLog;
use Modules\Assessment\Services\Contracts\CompetencyService as Contract;
use Modules\Shared\Services\EloquentQuery;

class CompetencyService extends EloquentQuery implements Contract
{
    public function __construct(Competency $model)
    {
        $this->setModel($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getForDepartment(string $departmentId)
    {
        return DepartmentCompetency::with('competency')
            ->where('department_id', $departmentId)
            ->get()
            ->pluck('competency');
    }

    /**
     * {@inheritdoc}
     */
    public function recordProgress(array $data): StudentCompetencyLog
    {
        return StudentCompetencyLog::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getProgressStats(string $registrationId): array
    {
        $logs = StudentCompetencyLog::with('competency')
            ->where('registration_id', $registrationId)
            ->get()
            ->groupBy('competency_id');

        $stats = [];
        foreach ($logs as $competencyId => $entryLogs) {
            $latest = $entryLogs->sortByDesc('created_at')->first();
            $stats[] = [
                'name' => $latest->competency->name,
                'score' => $latest->score,
            ];
        }

        return $stats;
    }
}

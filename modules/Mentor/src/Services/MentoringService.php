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

    /**
     * {@inheritdoc}
     */
    public function getUnifiedTimeline(string $registrationId): \Illuminate\Support\Collection
    {
        $visits = MentoringVisit::with('teacher')
            ->where('registration_id', $registrationId)
            ->get()
            ->map(function ($visit) {
                return [
                    'id' => $visit->id,
                    'type' => 'visit',
                    'date' => $visit->visit_date,
                    'causer' => $visit->teacher,
                    'title' => __('Kunjungan Lapangan'),
                    'content' => $visit->notes,
                    'metadata' => $visit->findings,
                ];
            });

        $logs = MentoringLog::with('causer')
            ->where('registration_id', $registrationId)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => 'log',
                    'date' => $log->created_at,
                    'causer' => $log->causer,
                    'title' => $log->subject,
                    'content' => $log->content,
                    'metadata' => $log->metadata,
                ];
            });

        return $visits->concat($logs)->sortByDesc('date')->values();
    }
}

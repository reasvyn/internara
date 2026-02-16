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

    /**
     * {@inheritdoc}
     */
    public function syncJournalCompetencies(string $journalEntryId, array $competencyIds): void
    {
        \Illuminate\Support\Facades\DB::table('journal_competency')
            ->where('journal_entry_id', $journalEntryId)
            ->delete();

        $data = collect($competencyIds)
            ->map(
                fn ($id) => [
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'journal_entry_id' => $journalEntryId,
                    'competency_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            )
            ->toArray();

        \Illuminate\Support\Facades\DB::table('journal_competency')->insert($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getClaimedCompetencies(string $registrationId): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('journal_competency')
            ->join(
                'journal_entries',
                'journal_competency.journal_entry_id',
                '=',
                'journal_entries.id',
            )
            ->join('competencies', 'journal_competency.competency_id', '=', 'competencies.id')
            ->where('journal_entries.registration_id', $registrationId)
            ->select('competencies.*', 'journal_entries.date as claimed_date')
            ->orderBy('journal_entries.date', 'desc')
            ->get();
    }
}

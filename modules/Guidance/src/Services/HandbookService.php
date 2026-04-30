<?php

declare(strict_types=1);

namespace Modules\Guidance\Services;

use Modules\Guidance\Models\Handbook;
use Modules\Guidance\Models\HandbookAcknowledgement;
use Modules\Guidance\Services\Contracts\HandbookService as HandbookServiceContract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Class HandbookService
 *
 * Implements the business logic for managing instructional handbooks and tracking student readiness.
 */
class HandbookService extends EloquentQuery implements HandbookServiceContract
{
    /**
     * Create a new HandbookService instance.
     */
    public function __construct(Handbook $model)
    {
        $this->setModel($model);
        $this->setSearchable(['title', 'description']);
        $this->setSortable(['title', 'version', 'is_mandatory']);
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledge(string $studentId, string $handbookId): bool
    {
        $acknowledgement = HandbookAcknowledgement::updateOrCreate(
            [
                'student_id' => $studentId,
                'handbook_id' => $handbookId,
            ],
            [
                'acknowledged_at' => now(),
            ],
        );

        return (bool) $acknowledgement;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAcknowledged(string $studentId, string $handbookId): bool
    {
        return HandbookAcknowledgement::where('student_id', $studentId)
            ->where('handbook_id', $handbookId)
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function hasCompletedMandatory(string $studentId): bool
    {
        $mandatoryCount = $this->query(['is_mandatory' => true, 'is_active' => true])->count();

        if ($mandatoryCount === 0) {
            return true;
        }

        $acknowledgedCount = HandbookAcknowledgement::where('student_id', $studentId)
            ->whereIn('handbook_id', function ($query) {
                $query
                    ->select('id')
                    ->from('handbooks')
                    ->where('is_mandatory', true)
                    ->where('is_active', true);
            })
            ->count();

        return $acknowledgedCount >= $mandatoryCount;
    }
}

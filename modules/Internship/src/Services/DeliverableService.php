<?php

declare(strict_types=1);

namespace Modules\Internship\Services;

use Illuminate\Http\UploadedFile;
use Modules\Internship\Models\InternshipDeliverable;
use Modules\Internship\Services\Contracts\DeliverableService as Contract;
use Modules\Shared\Services\EloquentQuery;

/**
 * Class DeliverableService
 *
 * Implementation for managing student internship deliverables.
 */
class DeliverableService extends EloquentQuery implements Contract
{
    public function __construct(InternshipDeliverable $model)
    {
        $this->setModel($model);
    }

    /**
     * {@inheritdoc}
     */
    public function submit(string $registrationId, string $type, UploadedFile $file): InternshipDeliverable
    {
        /** @var InternshipDeliverable $deliverable */
        $deliverable = $this->model->newQuery()->updateOrCreate(
            ['registration_id' => $registrationId, 'type' => $type],
            ['registration_id' => $registrationId, 'type' => $type]
        );

        $deliverable->addMedia($file)->toMediaCollection('file');
        $deliverable->setStatus('submitted', 'File uploaded by student.');

        return $deliverable;
    }

    /**
     * {@inheritdoc}
     */
    public function approve(string $deliverableId, ?string $reason = null): InternshipDeliverable
    {
        $deliverable = $this->find($deliverableId);
        $deliverable->setStatus('verified', $reason ?? 'Deliverable verified by authorized staff.');

        return $deliverable;
    }

    /**
     * {@inheritdoc}
     */
    public function reject(string $deliverableId, string $reason): InternshipDeliverable
    {
        $deliverable = $this->find($deliverableId);
        $deliverable->setStatus('rejected', $reason);

        return $deliverable;
    }

    /**
     * {@inheritdoc}
     */
    public function areAllDeliverablesVerified(string $registrationId): bool
    {
        $mandatoryTypes = ['report', 'presentation'];
        
        $verifiedCount = $this->model->newQuery()
            ->where('registration_id', $registrationId)
            ->whereIn('type', $mandatoryTypes)
            ->currentStatus('verified')
            ->count();

        return $verifiedCount === count($mandatoryTypes);
    }
}

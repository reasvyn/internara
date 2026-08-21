<?php

declare(strict_types=1);

namespace App\Certification\Actions;

use App\Certification\Data\BatchIssueCertificatesData;
use App\Certification\Jobs\BatchIssueCertificatesJob;
use App\Core\Actions\BaseCommandAction;

final class DispatchBatchIssueCertificatesAction extends BaseCommandAction
{
    public function execute(BatchIssueCertificatesData $data): void
    {
        BatchIssueCertificatesJob::dispatch(
            registrationIds: $data->registrationIds,
            status: $data->status,
            templateId: $data->templateId,
            issuedBy: (string) auth()->id(),
        )->onQueue('default');

        $this->log('certificate_batch_queued', null, [
            'registration_count' => count($data->registrationIds),
            'status' => $data->status,
            'template_id' => $data->templateId,
        ]);
    }
}

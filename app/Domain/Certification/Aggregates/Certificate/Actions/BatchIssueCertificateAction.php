<?php

declare(strict_types=1);

namespace App\Domain\Certification\Aggregates\Certificate\Actions;

use App\Domain\Certification\Aggregates\Certificate\Models\CertificateTemplate;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Enrollment\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class BatchIssueCertificateAction extends BaseAction
{
    public function __construct(protected readonly IssueCertificateAction $issueCertificate) {}

    public function execute(array $registrationIds, CertificateTemplate $template): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        $registrations = Registration::whereIn('id', $registrationIds)->get();

        foreach ($registrations as $registration) {
            DB::transaction(function () use ($registration, $template, &$results) {
                try {
                    $this->issueCertificate->execute($registration, $template);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = 'Registration '.$registration->id.': '.$e->getMessage();
                }
            });
        }

        $notFound = count($registrationIds) - $results['success'] - $results['failed'];
        if ($notFound > 0) {
            $results['failed'] += $notFound;
        }

        return $results;
    }

    public function executeFiltered(Builder $query, CertificateTemplate $template): array
    {
        $registrations = $query->get();

        return $this->execute($registrations->pluck('id')->toArray(), $template);
    }
}

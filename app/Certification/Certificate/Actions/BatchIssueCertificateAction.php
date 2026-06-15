<?php

declare(strict_types=1);

namespace App\Certification\Certificate\Actions;

use App\Certification\Certificate\Models\CertificateTemplate;
use App\Core\Actions\BaseProcessAction;
use App\Enrollment\Registration\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class BatchIssueCertificateAction extends BaseProcessAction
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
                    $results['errors'][] =
                        'Registration '.$registration->id.': '.$e->getMessage();
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

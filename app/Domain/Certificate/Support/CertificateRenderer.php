<?php

declare(strict_types=1);

namespace App\Domain\Certificate\Support;

use App\Domain\Certificate\Models\Certificate;
use App\Domain\Registration\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;

final readonly class CertificateRenderer
{
    private const string STORAGE_PATH = 'certificates';

    public function resolvePlaceholders(Registration $registration, Certificate $certificate): array
    {
        $user = $registration->mentee?->user;
        $profile = $user?->profile;
        $school = $registration->internship?->academicYear;
        $company = $registration->placement?->company;
        $assessment = $registration->assessment;

        return [
            '{student_name}' => $user?->name ?? '—',
            '{student_nis}' => $profile?->national_identifier ?? '—',
            '{school_name}' => $school?->name ?? '—',
            '{school_code}' => '',
            '{department_name}' => $profile?->department?->name ?? '—',
            '{company_name}' => $company?->name ?? '—',
            '{internship_name}' => $registration->internship?->name ?? '—',
            '{start_date}' => $registration->start_date?->format('d F Y') ?? '—',
            '{end_date}' => $registration->end_date?->format('d F Y') ?? '—',
            '{duration}' => $registration->start_date && $registration->end_date
                ? (int) ceil($registration->start_date->diffInMonths($registration->end_date)).' months'
                : '—',
            '{score}' => $assessment?->score ?? '—',
            '{score_letter}' => $assessment?->score
                ? match (true) {
                    $assessment->score >= 90 => 'A',
                    $assessment->score >= 80 => 'B',
                    $assessment->score >= 70 => 'C',
                    $assessment->score >= 60 => 'D',
                    default => 'E',
                }
                : '—',
            '{certificate_number}' => $certificate->certificate_number,
            '{issued_date}' => $certificate->issued_at?->format('d F Y') ?? now()->format('d F Y'),
            '{principal_name}' => '',
            '{supervisor_name}' => $registration->mentors()
                ->wherePivot('role', 'supervisor')
                ->first()?->user?->name ?? '—',
        ];
    }

    public function renderHtml(Registration $registration, Certificate $certificate): string
    {
        $placeholders = $this->resolvePlaceholders($registration, $certificate);
        $template = $certificate->template?->content_template ?? '<p>Certificate</p>';

        $html = str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template,
        );

        return Blade::render(
            string: $html,
            data: ['registration' => $registration, 'certificate' => $certificate],
            deleteCachedView: true,
        );
    }

    public function renderPdf(Registration $registration, Certificate $certificate): string
    {
        $html = $this->renderHtml($registration, $certificate);

        $layout = $certificate->template?->layout ?? 'portrait';

        return Pdf::loadHTML($html)
            ->setPaper('A4', $layout)
            ->output();
    }

    public function storePdf(Registration $registration, Certificate $certificate): string
    {
        $pdf = $this->renderPdf($registration, $certificate);

        $fileName = 'certificate_'.$certificate->certificate_number.'.pdf';
        $path = self::STORAGE_PATH.'/'.$fileName;

        Storage::disk('local')->put($path, $pdf);

        return $path;
    }

    public function getDiskPath(string $storagePath): string
    {
        return Storage::disk('local')->path($storagePath);
    }
}

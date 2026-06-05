<?php

declare(strict_types=1);

namespace App\Certification\Certificate\Http\Controllers;

use App\Certification\Certificate\Models\Certificate;
use App\Certification\Certificate\Support\CertificateRenderer;
use App\Core\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateDownloadController extends BaseController
{
    public function __construct(protected CertificateRenderer $renderer) {}

    public function __invoke(Certificate $certificate): StreamedResponse
    {
        abort_unless(auth()->check(), 403);

        $user = auth()->user();
        $isStudent = $user->hasRole('student');
        $isAdmin = $user->hasAnyRole(['super_admin', 'admin']);

        if ($isStudent) {
            $registration = $certificate->registration;
            abort_unless($registration->mentee?->user_id === $user->id, 403);
        } elseif (! $isAdmin) {
            abort(403);
        }

        $metadata = $certificate->metadata;
        $pdfPath = $metadata['pdf_path'] ?? null;

        if (! $pdfPath || ! Storage::disk('local')->exists($pdfPath)) {
            $registration = $certificate->registration;
            $pdfPath = $this->renderer->storePdf($registration, $certificate);
            $metadata['pdf_path'] = $pdfPath;
            $certificate->update(['metadata' => $metadata]);
        }

        $fileName = 'certificate_'.$certificate->certificate_number.'.pdf';

        return Storage::disk('local')->download($pdfPath, $fileName);
    }
}

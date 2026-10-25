<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Actions;

use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Certification\Domain\Certificate\Services\CertificateRenderer;
use App\Modules\Core\Actions\BaseCommandAction;
use Illuminate\Support\Facades\Storage;

final class GenerateCertificatePdfAction extends BaseCommandAction
{
    public function __construct(private readonly CertificateRenderer $renderer) {}

    /**
     * Lazily generate the certificate PDF on first download.
     *
     * Idempotent by design: an already stored file is returned untouched so
     * repeated downloads never re-render or overwrite the issued document.
     */
    public function execute(Certificate $certificate): string
    {
        $path = $this->renderer->pdfPath($certificate);

        if (Storage::disk('local')->exists($path)) {
            return $path;
        }

        $registration = $certificate->registration;
        $path = $this->renderer->storePdf($registration, $certificate);

        $this->log('certificate_pdf_generated', $certificate, [
            'certificate_number' => $certificate->certificate_number,
            'path' => $path,
        ]);

        return $path;
    }
}

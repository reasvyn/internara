<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Http\Controllers;

use App\Modules\Certification\Domain\Certificate\Actions\GenerateCertificatePdfAction;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Core\Http\Controllers\BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateDownloadController extends BaseController
{
    use AuthorizesRequests;

    public function __construct(private readonly GenerateCertificatePdfAction $generatePdf) {}

    public function __invoke(Certificate $certificate): StreamedResponse
    {
        $this->authorize('view', $certificate);

        $path = $this->generatePdf->execute($certificate);

        return Storage::disk('local')->download(
            $path,
            'certificate_'.$certificate->certificate_number.'.pdf',
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Http\Controllers;

use App\Modules\Certification\Domain\Certificate\Actions\ReadCertificateStatusAction;
use Illuminate\Http\Response;

final class VerifyCertificateController
{
    public function __invoke(string $qr_hash, ReadCertificateStatusAction $action): Response
    {
        $verification = $action->execute($qr_hash);

        return response()
            ->view('certification.verify', [
                'verification' => $verification,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}

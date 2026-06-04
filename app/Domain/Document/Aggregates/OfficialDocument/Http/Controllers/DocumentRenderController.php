<?php

declare(strict_types=1);

namespace App\Domain\Document\Aggregates\OfficialDocument\Http\Controllers;

use App\Domain\Core\Http\Controllers\BaseController;
use App\Domain\Document\Aggregates\OfficialDocument\Actions\RenderDocumentAction;
use App\Domain\Document\Models\Document;
use App\Domain\Document\Support\DocumentRenderer;
use App\Domain\Enrollment\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentRenderController extends BaseController
{
    public function show(Document $document, Registration $registration): StreamedResponse|RedirectResponse
    {
        $target = $registration->loadMissing([
            'mentee.user.profile',
            'internship',
            'placement.company',
        ]);

        $html = app(DocumentRenderer::class)->renderHtml($document, $target);

        return Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->download($document->slug.'-'.$registration->id.'.pdf');
    }

    public function store(Document $document, Registration $registration, RenderDocumentAction $action): RedirectResponse
    {
        $rendered = $action->execute($document, $registration);

        return redirect()->route('sysadmin.reports.index')
            ->with('success', 'Document generated successfully.');
    }
}

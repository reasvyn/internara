<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Document\RenderDocumentAction;
use App\Models\Document;
use App\Models\Registration;
use App\Support\DocumentRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentRenderController extends Controller
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

        return redirect()->route('admin.reports.index')
            ->with('success', 'Document generated successfully.');
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\OfficialDocument\Http\Controllers;

use App\Modules\Core\Http\Controllers\BaseController;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Domain\OfficialDocument\Actions\RenderDocumentAction;
use App\Modules\Document\Services\DocumentRenderer;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class DocumentRenderController extends BaseController
{
    public function __construct(
        private readonly DocumentRenderer $renderer,
    ) {}

    public function show(
        Document $document,
        Registration $registration,
    ): Response|RedirectResponse {
        $target = $registration->loadMissing([
            'mentee.user.profile',
            'internship',
            'placement.company',
        ]);

        $html = $this->renderer->renderHtml($document, $target);

        return Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->download($document->slug.'-'.$registration->id.'.pdf');
    }

    public function store(
        Document $document,
        Registration $registration,
        RenderDocumentAction $action,
    ): RedirectResponse {
        $rendered = $action->execute($document, $registration);

        return redirect()
            ->route('sysadmin.reports.index')
            ->with('success', 'Document generated successfully.');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Document\Support;

use App\Domain\Document\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;

final readonly class DocumentRenderer
{
    private const string STORAGE_PATH = 'generated-documents';

    public function renderHtml(Document $document, object $target): string
    {
        $compiled = Blade::render(
            string: $document->content,
            data: ['target' => $target],
            deleteCachedView: true,
        );

        return $compiled;
    }

    public function renderPdf(Document $document, object $target): string
    {
        $html = $this->renderHtml($document, $target);

        return Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->output();
    }

    public function storePdf(Document $document, object $target, ?string $suffix = null): string
    {
        $pdf = $this->renderPdf($document, $target);

        $fileName = str_replace('/', '-', $document->slug)
            .($suffix ? "-{$suffix}" : '')
            .'-'.now()->timestamp
            .'.pdf';

        $path = self::STORAGE_PATH.'/'.$fileName;

        Storage::disk('local')->put($path, $pdf);

        return $path;
    }
}

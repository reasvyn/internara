<?php

declare(strict_types=1);

namespace App\Domain\Document\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Document\Models\Document;
use App\Domain\Document\Support\DocumentRenderer;
use App\Domain\Registration\Models\Registration;
use Illuminate\Support\Facades\Storage;

class RenderDocumentAction extends BaseAction
{
    public function __construct(
        private readonly DocumentRenderer $renderer,
    ) {}

    public function execute(Document $document, Registration $registration): Document
    {
        $target = $registration->loadMissing([
            'mentee.user.profile',
            'internship',
            'placement.company',
            'mentors.user',
        ]);

        $path = $this->renderer->storePdf($document, $target, $registration->id);

        $rendered = Document::create([
            'name' => $document->name.' - '.($target->mentee->user->name ?? ''),
            'slug' => $document->slug.'-'.$registration->id.'-'.now()->timestamp,
            'category' => 'report',
            'description' => 'Rendered from template: '.$document->name,
            'content' => $document->content,
            'file_path' => $path,
            'is_active' => true,
        ]);

        $this->log('document_rendered', $rendered, [
            'template' => $document->name,
            'registration' => $registration->id,
            'student' => $target->mentee->user->name,
        ]);

        return $rendered;
    }

    public function download(Document $document): string
    {
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            return $document->file_path;
        }

        $mediaUrl = $document->getFirstMediaUrl('file');

        if ($mediaUrl) {
            return $mediaUrl;
        }

        throw new \RuntimeException('Document file not found.');
    }
}

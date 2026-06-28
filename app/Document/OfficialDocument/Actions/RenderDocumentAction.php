<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Document\Models\Document;
use App\Document\Services\DocumentRenderer;
use App\Enrollment\Registration\Models\Registration;
use Illuminate\Support\Facades\Storage;

final class RenderDocumentAction extends BaseCommandAction
{
    public function __construct(private readonly DocumentRenderer $renderer) {}

    public function execute(Document $document, Registration $registration): Document
    {
        $target = $registration->loadMissing([
            'mentee.user.profile',
            'internship',
            'placement.company',
            'mentors.user',
        ]);

        $path = $this->renderer->storePdf($document, $target, $registration->id);

        $rendered = $this->transaction(function () use ($document, $target, $registration, $path) {
            $doc = Document::create([
                'name' => $document->name.' - '.($target->mentee->user->name ?? ''),
                'slug' => $document->slug.'-'.$registration->id.'-'.now()->timestamp,
                'category' => 'report',
                'description' => 'Rendered from template: '.$document->name,
                'content' => $document->content,
                'file_path' => $path,
                'is_active' => true,
                'template_version' => $document->template_version,
                'template_id' => $document->id,
            ]);

            $this->log('document_rendered', $doc, [
                'template' => $document->name,
                'registration' => $registration->id,
                'student' => $target->mentee->user->name,
            ]);

            return $doc;
        });

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

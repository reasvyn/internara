<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\OfficialDocument\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Services\DocumentRenderer;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;

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
                'title' => $document->title.' - '.($target->mentee->user->name ?? ''),
                'slug' => $document->slug.'-'.$registration->id.'-'.now()->timestamp,
                'type' => 'report',
                'content' => $document->content,
                'file_path' => $path,
                'is_active' => true,
            ]);

            $this->log('document_rendered', $doc, [
                'template' => $document->title,
                'registration' => $registration->id,
                'student' => $target->mentee->user->name ?? 'Unknown',
            ]);

            return $doc;
        });

        return $rendered;
    }
}

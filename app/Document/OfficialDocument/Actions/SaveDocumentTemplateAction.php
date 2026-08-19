<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Document\Models\Document;
use Illuminate\Support\Str;

final class SaveDocumentTemplateAction extends BaseCommandAction
{
    public function execute(array $data): Document
    {
        $title = $data['title'] ?? '';
        $slug = Str::of($title)->slug()->toString();

        return $this->transaction(function () use ($data, $slug, $title) {
            $document = Document::updateOrCreate(
                ['id' => $data['id'] ?? null],
                [
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $data['content'] ?? null,
                    'type' => $data['type'] ?? 'template',
                    'metadata' => array_filter([
                        'description' => $data['description'] ?? null,
                    ]),
                    'is_active' => $data['is_active'] ?? true,
                ],
            );

            $this->log('document_template_saved', $document, ['title' => $document->title]);

            return $document;
        });
    }
}

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
        $slug = Str::of($data['name'])->slug()->toString();

        return $this->transaction(function () use ($data, $slug) {
            $document = Document::updateOrCreate(
                ['id' => $data['id'] ?? null],
                [
                    'title' => $data['name'] ?? $data['title'] ?? '',
                    'slug' => $slug,
                    'content' => $data['content'] ?? null,
                    'type' => $data['type'] ?? 'template',
                    'is_active' => $data['is_active'] ?? true,
                ],
            );

            $this->log('document_template_saved', $document, ['name' => $document->name]);

            return $document;
        });
    }
}

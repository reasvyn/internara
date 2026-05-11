<?php

declare(strict_types=1);

namespace App\Actions\Document;

use App\Models\Document;
use Illuminate\Support\Str;

class SaveDocumentTemplateAction
{
    public function execute(array $data): Document
    {
        $slug = Str::of($data['name'])->slug()->toString();

        return Document::updateOrCreate(
            ['id' => $data['id'] ?? null],
            array_merge($data, ['slug' => $slug]),
        );
    }
}

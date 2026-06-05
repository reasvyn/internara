<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Actions;

use App\Core\Actions\BaseAction;
use App\Document\Models\Document;
use Illuminate\Support\Str;

final class SaveDocumentTemplateAction extends BaseAction
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

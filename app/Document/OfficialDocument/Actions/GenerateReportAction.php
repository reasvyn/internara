<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Actions;

use App\Core\Actions\BaseAction;
use App\Document\Models\Document;
use Illuminate\Support\Facades\Storage;

final class GenerateReportAction extends BaseAction
{
    public function execute(array $data): Document
    {
        $slug = $data['type'].'-'.now()->timestamp;
        $content = json_encode(
            [
                'type' => $data['type'],
                'generated_at' => now()->toIso8601String(),
                'parameters' => $data['parameters'] ?? [],
            ],
            JSON_PRETTY_PRINT,
        );

        $fileName = $slug.'.json';
        Storage::disk('local')->put("reports/{$fileName}", $content);

        return Document::create([
            'name' => $data['name'],
            'slug' => $slug,
            'category' => 'report',
            'description' => $data['description'] ?? 'Auto-generated report',
            'content' => $content,
            'file_path' => "reports/{$fileName}",
            'is_active' => true,
        ]);
    }
}

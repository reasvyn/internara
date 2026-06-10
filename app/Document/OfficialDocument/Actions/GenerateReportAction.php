<?php

declare(strict_types=1);

namespace App\Document\OfficialDocument\Actions;

use App\Core\Actions\BaseAction;
use App\Document\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

final class GenerateReportAction extends BaseAction
{
    public function execute(array $data): Document
    {
        $validated = Validator::validate($data, [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'parameters' => ['nullable', 'array'],
        ]);

        $slug = $validated['type'].'-'.now()->timestamp;
        $content = json_encode(
            [
                'type' => $validated['type'],
                'generated_at' => now()->toIso8601String(),
                'parameters' => $validated['parameters'] ?? [],
            ],
            JSON_PRETTY_PRINT,
        );

        $fileName = $slug.'.json';
        Storage::disk('local')->put("reports/{$fileName}", $content);

        return $this->transaction(function () use ($validated, $slug, $content, $fileName) {
            $document = Document::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'category' => 'report',
                'description' => $validated['description'] ?? 'Auto-generated report',
                'content' => $content,
                'file_path' => "reports/{$fileName}",
                'is_active' => true,
            ]);

            $this->log('report_generated', $document, [
                'type' => $validated['type'],
            ]);

            return $document;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Document;

use App\Models\Document;

class GenerateReportAction
{
    public function execute(array $data): Document
    {
        return Document::create([
            'name' => $data['name'],
            'slug' => $data['type'].'-'.now()->timestamp,
            'category' => 'report',
            'description' => 'Auto-generated report',
            'content' => json_encode(['type' => $data['type'], 'generated_at' => now()->toIso8601String()]),
            'is_active' => true,
        ]);
    }
}

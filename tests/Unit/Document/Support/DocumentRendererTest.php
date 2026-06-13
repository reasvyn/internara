<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Document\Support\DocumentRenderer;

test('renderHtml compiles blade content', function () {
    $renderer = app(DocumentRenderer::class);
    $document = Document::factory()->make(['content' => '<p>Hello {{ \$target->name ?? "World" }}</p>']);

    $target = new class
    {
        public string $name = 'Test';
    };

    $html = $renderer->renderHtml($document, $target);

    expect($html)->toContain('Hello');
});

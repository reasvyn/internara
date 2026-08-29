<?php

declare(strict_types=1);

use App\Modules\Document\Models\Document;
use App\Modules\Document\Domain\OfficialDocument\Actions\SaveDocumentTemplateAction;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('PKYX6-FR-TM2: template is created from title, type and content with slug from title', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $document = app(SaveDocumentTemplateAction::class)->execute([
        'title' => 'Surat Izin PKL',
        'type' => 'permit',
        'content' => '<p>Content body</p>',
        'description' => 'Template surat izin',
    ]);

    expect($document)->toBeInstanceOf(Document::class)
        ->and($document->title)->toBe('Surat Izin PKL')
        ->and($document->type)->toBe('permit')
        ->and($document->content)->toBe('<p>Content body</p>')
        ->and($document->metadata['description'])->toBe('Template surat izin')
        ->and($document->is_active)->toBeTrue();
});

test('PKYX6-FR-TM5: slug is unique and regenerated from the title', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $action = app(SaveDocumentTemplateAction::class);
    $document = $action->execute([
        'title' => 'Surat Izin',
        'type' => 'permit',
        'content' => '<p>Content body</p>',
    ]);
    $updated = $action->execute([
        'id' => $document->id,
        'title' => 'Surat Izin Baru',
        'type' => 'permit',
        'content' => '<p>Updated body</p>',
    ]);

    expect($document->slug)->toBe('surat-izin')
        ->and($updated->id)->toBe($document->id)
        ->and($updated->slug)->toBe('surat-izin-baru');
});

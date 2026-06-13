<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Document\OfficialDocument\Actions\DeleteReportAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes document report', function () {
    $document = Document::factory()->create();

    app(DeleteReportAction::class)->execute($document);

    $this->assertModelMissing($document);
});

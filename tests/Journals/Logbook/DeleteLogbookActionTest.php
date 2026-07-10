<?php

declare(strict_types=1);

use App\Journals\Logbook\Actions\DeleteLogbookAction;
use App\Journals\Logbook\Models\Logbook;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('deletes logbook entry', function () {
    $entry = Logbook::factory()->create();

    app(DeleteLogbookAction::class)->execute($entry);

    $this->assertModelMissing($entry);
});

test('deletes logbook entry removes from database', function () {
    $entry = Logbook::factory()->create();

    app(DeleteLogbookAction::class)->execute($entry);

    $this->assertDatabaseMissing('logbooks', ['id' => $entry->id]);
});

test('deleting non-existent entry throws no error', function () {
    $entry = Logbook::factory()->create();
    $id = $entry->id;

    app(DeleteLogbookAction::class)->execute($entry);

    expect(Logbook::find($id))->toBeNull();
});

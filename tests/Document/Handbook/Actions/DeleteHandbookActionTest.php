<?php

declare(strict_types=1);

use App\Document\Enums\DocumentCategory;
use App\Document\Handbook\Actions\DeleteHandbookAction;
use App\Document\Models\Document;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    test()->actingAs($admin);
});

test('deletes handbook', function () {
    $handbook = Document::factory()->create([
        'type' => DocumentCategory::HANDBOOK->value,
    ]);

    app(DeleteHandbookAction::class)->execute($handbook);

    expect(Document::find($handbook->id))->toBeNull();
});

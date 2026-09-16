<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateDocumentAction;
use App\Modules\Document\Jobs\GenerateDocumentJob;
use App\Modules\Document\Models\Document;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

describe('7H5D6: batched official letters', function () {
    test('7H5D6-UC-OFFD-004: supervisor batch queues one job per letter on the documents queue', function (): void {
        Queue::fake();

        $documents = Document::factory()->count(3)->create([
            'content' => '<p>Assignment for {{ $target->name }}</p>',
        ]);

        foreach ($documents as $document) {
            GenerateDocumentJob::dispatch($document->id);
        }

        Queue::assertPushedOn('documents', GenerateDocumentJob::class);
        expect(Queue::pushed(GenerateDocumentJob::class))->toHaveCount(3);
        expect((new GenerateDocumentJob($documents->first()->id))->queue)->toBe('documents');
    });

    test('7H5D6-FR-OFFD-012: queued batch renders each letter while one bad record leaves the rest untouched', function (): void {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $first = Document::factory()->create(['content' => '<p>Letter one for {{ $target->name }}</p>']);
        $second = Document::factory()->create(['content' => '<p>Letter two for {{ $target->name }}</p>']);

        (new GenerateDocumentJob($first->id))->handle(app(GenerateDocumentAction::class));
        (new GenerateDocumentJob($second->id))->handle(app(GenerateDocumentAction::class));

        expect($first->fresh()->file_path)->not->toBeNull()
            ->and($second->fresh()->file_path)->not->toBeNull()
            ->and($first->fresh()->file_path)->not->toBe($second->fresh()->file_path)
            ->and(Storage::disk('local')->exists($first->fresh()->file_path))->toBeTrue()
            ->and(Storage::disk('local')->exists($second->fresh()->file_path))->toBeTrue();

        $goodBefore = $first->fresh()->file_path;

        try {
            (new GenerateDocumentJob('00000000-0000-0000-0000-000000000000'))->handle(app(GenerateDocumentAction::class));
            expect(false)->toBeTrue('expected a missing document to abort its job');
        } catch (ModelNotFoundException) {
            expect(true)->toBeTrue();
        }

        expect($first->fresh()->file_path)->toBe($goodBefore);
    });
});

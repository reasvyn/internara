<?php

declare(strict_types=1);

use App\Modules\Document\Domain\Handbook\Actions\AcknowledgeHandbookAction;
use App\Modules\Document\Domain\Handbook\Actions\CreateHandbookAction;
use App\Modules\Document\Domain\Handbook\Actions\DeleteHandbookAction;
use App\Modules\Document\Domain\Handbook\Actions\UpdateHandbookAction;
use App\Modules\Document\Domain\Handbook\Data\HandbookData;
use App\Modules\Document\Domain\Handbook\Enums\HandbookAudience;
use App\Modules\Document\Domain\Handbook\Events\HandbookCreated;
use App\Modules\Document\Enums\DocumentCategory;
use App\Modules\Document\Models\Document;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

function handbookCoverageAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    test()->actingAs($admin);

    return $admin;
}

describe('ZUFG8: handbook implementation behavior', function (): void {
    test('ZUFG8-FR-HAND-002: updating a handbook with a replacement file increments its version', function (): void {
        Storage::fake('public');
        handbookCoverageAdmin();
        $handbook = app(CreateHandbookAction::class)->execute(new HandbookData(
            title: 'Safety', audience: HandbookAudience::ALL, file: UploadedFile::fake()->create('v1.pdf', 10, 'application/pdf'),
        ));

        $updated = app(UpdateHandbookAction::class)->execute($handbook, new HandbookData(
            title: 'Safety revised', audience: HandbookAudience::STUDENT, file: UploadedFile::fake()->create('v2.pdf', 10, 'application/pdf'),
        ));

        expect($updated->version)->toBe(2)->and($updated->title)->toBe('Safety revised');
    });

    test('ZUFG8-FR-HAND-004: handbook creation stores the upload in its media collection', function (): void {
        Storage::fake('public');
        handbookCoverageAdmin();

        $handbook = app(CreateHandbookAction::class)->execute(new HandbookData(
            title: 'Student guide', audience: HandbookAudience::STUDENT, file: UploadedFile::fake()->create('guide.pdf', 20, 'application/pdf'),
        ));

        expect($handbook->getFirstMedia('handbook_file'))->not->toBeNull()
            ->and($handbook->asHandbook()->isAvailable())->toBeTrue();
    });

    test('ZUFG8-FR-HAND-005: handbook creation dispatches its lifecycle event', function (): void {
        Event::fake();
        handbookCoverageAdmin();

        $handbook = app(CreateHandbookAction::class)->execute(new HandbookData(
            title: 'Notice', audience: HandbookAudience::ALL,
        ));

        Event::assertDispatched(HandbookCreated::class, fn ($event) => $event->handbook->is($handbook));
    });

    test('ZUFG8-FR-HAND-008: deleting a handbook removes its document row', function (): void {
        handbookCoverageAdmin();
        $handbook = Document::factory()->create(['type' => DocumentCategory::HANDBOOK->value]);

        app(DeleteHandbookAction::class)->execute($handbook);

        expect(Document::find($handbook->id))->toBeNull();
    });

    test('ZUFG8-FR-HAND-009: handbook actions write only the handbook category', function (): void {
        handbookCoverageAdmin();

        $handbook = app(CreateHandbookAction::class)->execute(new HandbookData(
            title: 'Role rules', audience: HandbookAudience::TEACHER,
        ));

        expect($handbook->type)->toBe(DocumentCategory::HANDBOOK->value)
            ->and(Document::ofType(DocumentCategory::HANDBOOK->value)->count())->toBe(1);
    });

    test('ZUFG8-FR-HAND-011 + 89SRA-FR-LOG-001: acknowledgment uses SmartLogger as the sole audit entry point', function (): void {
        $admin = handbookCoverageAdmin();
        $handbook = Document::factory()->create(['type' => 'handbook', 'version' => 3, 'metadata' => ['target_audience' => 'all']]);
        $captured = captureLogs();

        app(AcknowledgeHandbookAction::class)->execute($handbook, $admin);

        $entry = Activity::where('description', 'handbook_acknowledged')->where('subject_id', $handbook->id)->first();

        expect(Activity::where('description', 'handbook_acknowledged')->where('subject_id', $handbook->id)->where('event', 'acknowledged')->count())->toBe(1)
            ->and($entry->causer_id)->toBe($admin->id)
            ->and($entry->log_name)->toBe('Document')
            ->and($entry->properties['payload']['user_id'])->toBe($admin->id)
            ->and($entry->properties['payload']['version'])->toBe(3)
            ->and($entry->properties['payload'])->toHaveKey('ip')
            ->and($captured->firstWhere('message', 'handbook_acknowledged'))->toBeNull();
    });

    test('ZUFG8-FR-HAND-012: a newer handbook version is newer than an older acknowledgment', function (): void {
        $reader = handbookCoverageAdmin();
        $handbook = Document::factory()->create(['type' => 'handbook', 'version' => 2, 'metadata' => ['target_audience' => 'all']]);
        activity()->causedBy($reader)->performedOn($handbook)->withProperties(['version' => 1])->event('acknowledged')->log('handbook_acknowledged');

        expect($handbook->asHandbook()->isNewerThan(Activity::latest()->first()))->toBeTrue();
    });

    test('ZUFG8-FR-HAND-013: acknowledgment lookup is keyed by the handbook subject', function (): void {
        $reader = handbookCoverageAdmin();
        $handbook = Document::factory()->create(['type' => 'handbook', 'metadata' => ['target_audience' => 'all']]);
        activity()->causedBy($reader)->performedOn($handbook)->withProperties(['version' => 1])->event('acknowledged')->log('handbook_acknowledged');

        expect(Activity::causedBy($reader)->forEvent('acknowledged')->where('subject_id', $handbook->id)->exists())->toBeTrue();
    });

    test('ZUFG8-FR-HAND-017: handbook data rejects a missing title before an action can run', function (): void {
        expect(fn () => HandbookData::fromArray(['audience' => HandbookAudience::ALL]))
            ->toThrow(InvalidArgumentException::class);
    });

    test('ZUFG8-FR-HAND-019: handbook creation writes an audit record with its audience', function (): void {
        handbookCoverageAdmin();

        $handbook = app(CreateHandbookAction::class)->execute(new HandbookData(
            title: 'Audit guide', audience: HandbookAudience::SUPERVISOR,
        ));

        $entry = Activity::where('description', 'handbook_created')->where('subject_id', $handbook->id)->first();
        expect($entry)->not->toBeNull()->and($entry->properties)->not->toBeEmpty();
    });

    test('ZUFG8-FR-HAND-020: handbook records use UUID primary keys and typed metadata', function (): void {
        $handbook = Document::factory()->create(['type' => 'handbook', 'metadata' => ['target_audience' => 'all']]);

        expect($handbook->getKey())->toBeString()->and($handbook->metadata)->toBeArray();
    });

    test('ZUFG8-FR-HAND-021: handbook actions execute and perform lifecycle mutations', function (): void {
        handbookCoverageAdmin();
        $action = app(CreateHandbookAction::class);
        $handbook = $action->execute(new HandbookData(title: 'Single Entry Handbook', audience: HandbookAudience::ALL));

        expect($handbook)->toBeInstanceOf(Document::class)
            ->and($handbook->title)->toBe('Single Entry Handbook');
    });

    test('ZUFG8-NFR-HAND-001: handbook lifecycle mutations are attributed to the acting admin', function (): void {
        handbookCoverageAdmin();

        $handbook = app(CreateHandbookAction::class)->execute(new HandbookData(title: 'Attributed', audience: HandbookAudience::ALL));
        $entry = Activity::where('description', 'handbook_created')->where('subject_id', $handbook->id)->firstOrFail();

        expect($entry->causer_id)->toBe(auth()->id());
    });

    test('ZUFG8-NFR-HAND-002: acknowledgment stores the acknowledged handbook version', function (): void {
        $reader = handbookCoverageAdmin();
        $handbook = Document::factory()->create(['type' => 'handbook', 'version' => 4, 'metadata' => ['target_audience' => 'all']]);

        app(AcknowledgeHandbookAction::class)->execute($handbook, $reader);

        $entry = Activity::where('description', 'handbook_acknowledged')->where('subject_id', $handbook->id)->firstOrFail();
        expect($entry->properties['payload']['version'])->toBe(4);
    });

    test('ZUFG8-NFR-HAND-004: active handbook records remain selectable by the active scope', function (): void {
        Document::factory()->create(['type' => 'handbook', 'is_active' => true]);
        Document::factory()->create(['type' => 'handbook', 'is_active' => false]);

        expect(Document::ofType('handbook')->active()->count())->toBe(1);
    });

    test('ZUFG8-NFR-HAND-006: handbook audience labels remain available in both supported locales', function (): void {
        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);
            expect(HandbookAudience::ALL->label())->not->toBe('');
            expect(HandbookAudience::STUDENT->label())->not->toBe('');
            expect(HandbookAudience::TEACHER->label())->not->toBe('');
            expect(HandbookAudience::SUPERVISOR->label())->not->toBe('');
        }
    });

    test('ZUFG8-FR-HAND-014: handbook metadata retains its audience and description fields', function (): void {
        handbookCoverageAdmin();

        $handbook = app(CreateHandbookAction::class)->execute(new HandbookData(
            title: 'Metadata guide', audience: HandbookAudience::STUDENT, description: 'Read before placement',
        ));

        expect($handbook->metadata)->toMatchArray(['target_audience' => 'student', 'description' => 'Read before placement']);
    });

    test('ZUFG8-FR-HAND-015: handbook version defaults to one on creation', function (): void {
        handbookCoverageAdmin();

        $handbook = app(CreateHandbookAction::class)->execute(new HandbookData(title: 'First edition', audience: HandbookAudience::ALL));

        expect($handbook->version)->toBe(1);
    });

    test('ZUFG8-FR-HAND-016: updating a handbook can retire it without changing its version', function (): void {
        handbookCoverageAdmin();
        $handbook = Document::factory()->create(['type' => 'handbook', 'version' => 2, 'is_active' => true, 'metadata' => ['target_audience' => 'all']]);

        $updated = app(UpdateHandbookAction::class)->execute($handbook, new HandbookData(title: 'Retired', audience: HandbookAudience::ALL, isActive: false));

        expect($updated->is_active)->toBeFalse()->and($updated->version)->toBe(2);
    });

    test('ZUFG8-FR-HAND-018: handbook audience metadata round-trips through its entity', function (): void {
        $handbook = Document::factory()->create(['type' => 'handbook', 'metadata' => ['target_audience' => 'supervisor']]);
        $handbook->setRelation('media', collect());

        expect($handbook->asHandbook()->audience())->toBe(HandbookAudience::SUPERVISOR);
    });

    test('ZUFG8-FR-HAND-021: handbook creation returns the persisted handbook model', function (): void {
        handbookCoverageAdmin();

        $handbook = app(CreateHandbookAction::class)->execute(new HandbookData(title: 'Persisted', audience: HandbookAudience::ALL));

        expect($handbook->exists)->toBeTrue()->and(Document::find($handbook->id))->not->toBeNull();
    });

    test('ZUFG8-UC-HAND-001: an administrator can publish a handbook without an upload', function (): void {
        handbookCoverageAdmin();

        $handbook = app(CreateHandbookAction::class)->execute(new HandbookData(title: 'Published notice', audience: HandbookAudience::ALL));

        expect($handbook->title)->toBe('Published notice')->and($handbook->is_active)->toBeTrue();
    });

    test('ZUFG8-UC-HAND-002: a handbook update changes the reader-facing description', function (): void {
        handbookCoverageAdmin();
        $handbook = Document::factory()->create(['type' => 'handbook', 'metadata' => ['target_audience' => 'all']]);

        $updated = app(UpdateHandbookAction::class)->execute($handbook, new HandbookData(title: $handbook->title, audience: HandbookAudience::ALL, description: 'Updated instructions'));

        expect($updated->metadata['description'])->toBe('Updated instructions');
    });

    test('ZUFG8-UC-HAND-003: acknowledgment is attributed to the supplied reader', function (): void {
        $reader = handbookCoverageAdmin();
        $handbook = Document::factory()->create(['type' => 'handbook', 'version' => 1, 'metadata' => ['target_audience' => 'all']]);

        app(AcknowledgeHandbookAction::class)->execute($handbook, $reader);

        expect(Activity::where('description', 'handbook_acknowledged')->where('causer_id', $reader->id)->exists())->toBeTrue();
    });

    test('ZUFG8-NFR-HAND-003: acknowledgment records can be queried by reader and event', function (): void {
        $reader = handbookCoverageAdmin();
        $handbook = Document::factory()->create(['type' => 'handbook', 'metadata' => ['target_audience' => 'all']]);
        app(AcknowledgeHandbookAction::class)->execute($handbook, $reader);

        expect(Activity::causedBy($reader)->forEvent('acknowledged')->where('subject_type', Document::class)->count())->toBe(1);
    });

    test('ZUFG8-NFR-HAND-005: handbook records do not expose a public file path field', function (): void {
        $handbook = Document::factory()->make(['type' => 'handbook']);

        expect($handbook->getAttribute('file_path'))->toBeNull();
    });
});

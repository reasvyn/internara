<?php

declare(strict_types=1);

use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Partner\Domain\Partnership\Actions\BatchDeletePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\CreatePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\DeletePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\RenewPartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\TerminatePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Actions\UpdatePartnershipAction;
use App\Modules\Partner\Domain\Partnership\Data\PartnershipData;
use App\Modules\Partner\Domain\Partnership\Entities\PartnershipState;
use App\Modules\Partner\Domain\Partnership\Enums\PartnershipStatus;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipCreated;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipDeleted;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipRenewed;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipTerminated;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipUpdated;
use App\Modules\Partner\Domain\Partnership\Listeners\ClearDashboardOnPartnershipChange;
use App\Modules\Partner\Domain\Partnership\Livewire\PartnershipManager;
use App\Modules\Partner\Domain\Partnership\Models\Partnership;
use App\Modules\User\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function partnershipDocsData(string $agreementNumber, ?Company $company = null): PartnershipData
{
    $company ??= Company::factory()->create();

    return new PartnershipData(
        companyId: $company->id,
        agreementNumber: $agreementNumber,
        title: 'Kerja sama PKL '.$agreementNumber,
        startDate: '2026-07-01',
        endDate: '2027-06-30',
        contactPersonName: 'Budi Santoso',
    );
}

describe('NTHQA: partnership documents, events, manager, and audit', function (): void {
    test('NTHQA-UC-PART-006: replacing the MoU leaves exactly one document, the fresh scan', function (): void {
        $partnership = Partnership::factory()->create();
        $first = null;
        $second = null;

        try {
            $first = $partnership->addMedia(UploadedFile::fake()->image('crooked-photo.jpg', 400, 400))
                ->usingFileName('crooked-photo.jpg')
                ->toMediaCollection(Partnership::COLLECTION_MOU);
            $firstPath = $first->getPath();

            $second = $partnership->refresh()->addMedia(UploadedFile::fake()->image('flatbed-scan.jpg', 400, 400))
                ->usingFileName('flatbed-scan.jpg')
                ->toMediaCollection(Partnership::COLLECTION_MOU);

            $media = $partnership->refresh()->getMedia(Partnership::COLLECTION_MOU);

            expect($media)->toHaveCount(1);
            expect($media->first()->file_name)->toBe('flatbed-scan.jpg');
            expect(file_exists($firstPath))->toBeFalse();
            expect(file_exists($second->getPath()))->toBeTrue();
        } finally {
            $second?->delete();
            $first?->delete();
        }
    });

    test('NTHQA-FR-PART-014: the MoU collection structurally keeps a single file per agreement', function (): void {
        $partnership = Partnership::factory()->create();
        $media = null;

        try {
            $partnership->addMedia(UploadedFile::fake()->image('first.jpg', 400, 400))
                ->usingFileName('first.jpg')
                ->toMediaCollection(Partnership::COLLECTION_MOU);
            $media = $partnership->refresh()->addMedia(UploadedFile::fake()->image('second.jpg', 400, 400))
                ->usingFileName('second.jpg')
                ->toMediaCollection(Partnership::COLLECTION_MOU);

            expect($partnership->refresh()->getMedia(Partnership::COLLECTION_MOU))->toHaveCount(1);
            $this->assertDatabaseCount('media', 1);
        } finally {
            $media?->delete();
        }
    });

    test('NTHQA-FR-PART-015: the thumbnail exists on first view with no worker involved', function (): void {
        Queue::fake();
        $partnership = Partnership::factory()->create();
        $media = null;

        try {
            $media = $partnership->addMedia(UploadedFile::fake()->image('mou.jpg', 800, 600))
                ->toMediaCollection(Partnership::COLLECTION_MOU);

            expect($media->hasGeneratedConversion('thumb'))->toBeTrue();
            expect(file_exists($media->getPath('thumb')))->toBeTrue();
        } finally {
            $media?->delete();
        }
    });

    test('NTHQA-NFR-PART-005: listing thumbnails never wait on queue infrastructure', function (): void {
        Queue::fake();
        $partnership = Partnership::factory()->create();
        $media = null;

        try {
            $media = $partnership->addMedia(UploadedFile::fake()->image('listing.jpg', 800, 600))
                ->toMediaCollection(Partnership::COLLECTION_MOU);

            Queue::assertNothingPushed();
            expect(file_exists($media->getPath('thumb')))->toBeTrue();
        } finally {
            $media?->delete();
        }
    });

    test('NTHQA-FR-PART-017: every mutation dispatches its named event after commit', function (): void {
        Event::fake([PartnershipCreated::class]);
        $created = app(CreatePartnershipAction::class)->execute(partnershipDocsData('MOU/2026/EVT-1'));
        Event::assertDispatched(PartnershipCreated::class, fn ($event) => $event->partnership->is($created));

        Event::fake([PartnershipUpdated::class]);
        $updated = app(UpdatePartnershipAction::class)->execute(
            $created, partnershipDocsData('MOU/2026/EVT-1')
        );
        Event::assertDispatched(PartnershipUpdated::class, fn ($event) => $event->partnership->is($updated));

        Event::fake([PartnershipTerminated::class]);
        $terminated = app(TerminatePartnershipAction::class)->execute($created->refresh());
        Event::assertDispatched(PartnershipTerminated::class, fn ($event) => $event->partnership->is($terminated));

        Event::fake([PartnershipDeleted::class]);
        app(DeletePartnershipAction::class)->execute($terminated->refresh());
        Event::assertDispatched(PartnershipDeleted::class);

        Event::fake([PartnershipRenewed::class]);
        $lapsed = Partnership::factory()->expired()->create();
        $fresh = app(RenewPartnershipAction::class)->execute($lapsed, partnershipDocsData('MOU/2027/EVT-2'));
        Event::assertDispatched(PartnershipRenewed::class, fn ($event) => $event->newPartnership->is($fresh)
            && $event->oldPartnership->is($lapsed));
    });

    test('NTHQA-FR-PART-017: dashboard cache clears on every handled partnership event', function (): void {
        $key = config('cache-keys.admin_dashboard_stats');
        $partnership = Partnership::factory()->create();
        $renewed = Partnership::factory()->create();
        $listener = app(ClearDashboardOnPartnershipChange::class);

        foreach ([
            new PartnershipCreated($partnership),
            new PartnershipUpdated($partnership),
            new PartnershipDeleted($partnership),
            new PartnershipTerminated($partnership),
            new PartnershipRenewed($renewed, $partnership),
        ] as $event) {
            Cache::put($key, 'stale-stats');
            $listener->handle($event);
            expect(Cache::get($key))->toBeNull();
        }

        Queue::fake();
        Cache::put($key, 'stale-stats');
        $live = Partnership::factory()->create();
        event(new PartnershipTerminated($live));

        expect(Cache::get($key))->toBeNull();
        Queue::assertPushed(CallQueuedListener::class);
    });

    test('NTHQA-FR-PART-019: manager lists company-aware rows with search, filters, and stats', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $company = Company::factory()->create(['name' => 'Acme Manufacturing NTHQA']);
        Partnership::factory()->create([
            'company_id' => $company->id,
            'agreement_number' => 'NTHQA-MGR-001',
            'title' => 'Alpha cooperation',
            'status' => PartnershipStatus::ACTIVE->value,
            'end_date' => now()->addDays(10)->format('Y-m-d'),
        ]);
        Partnership::factory()->create([
            'agreement_number' => 'NTHQA-MGR-002',
            'title' => 'Beta deal',
            'status' => PartnershipStatus::EXPIRED->value,
        ]);

        Livewire::test(PartnershipManager::class)
            ->assertSee('Acme Manufacturing NTHQA')
            ->set('search', 'NTHQA-MGR-001')
            ->assertSee('NTHQA-MGR-001')
            ->assertDontSee('NTHQA-MGR-002')
            ->set('search', '')
            ->set('filters.status', PartnershipStatus::EXPIRED->value)
            ->assertSee('NTHQA-MGR-002')
            ->assertDontSee('NTHQA-MGR-001');

        $stats = Livewire::test(PartnershipManager::class)->get('stats');

        expect($stats['total'])->toBe(2);
        expect($stats['active'])->toBe(1);
        expect($stats['expired'])->toBe(1);
        expect($stats['expiring_soon'])->toBe(1);
    });

    test('NTHQA-FR-PART-021: mutations write SmartLogger activity entries with the actor', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $partnership = app(CreatePartnershipAction::class)->execute(partnershipDocsData('MOU/2026/AUD-1'));
        app(TerminatePartnershipAction::class)->execute($partnership);

        $created = DB::table('activity_log')->where('description', 'partnership_created')->first();
        $terminated = DB::table('activity_log')->where('description', 'partnership_terminated')->first();

        expect($created)->not->toBeNull();
        expect((string) $created->causer_id)->toBe((string) $admin->getKey());
        expect($terminated)->not->toBeNull();
        expect((string) $terminated->causer_id)->toBe((string) $admin->getKey());
    });

    test('NTHQA-NFR-PART-007: batch operations report exact deleted and skipped counts', function (): void {
        $expired = Partnership::factory()->expired()->create();
        $active = Partnership::factory()->create(['status' => PartnershipStatus::ACTIVE->value]);

        $result = app(BatchDeletePartnershipAction::class)->execute([
            $expired->id, $active->id, '00000000-0000-0000-0000-000000000000',
        ]);

        expect($result)->toBe(['deleted' => 1, 'blocked' => 1]);
        $this->assertModelMissing($expired);
        $this->assertModelExists($active->fresh());
    });

    test('NTHQA-NFR-PART-008: invalid windows are refused inline with no row written', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $company = Company::factory()->create();

        Livewire::test(PartnershipManager::class)
            ->call('create')
            ->set('form.company_id', $company->id)
            ->set('form.agreement_number', 'NTHQA-BAD-001')
            ->set('form.title', 'Backward window')
            ->set('form.start_date', '2026-07-01')
            ->set('form.end_date', '2026-06-01')
            ->call('save')
            ->assertHasErrors(['form.end_date']);

        expect(Partnership::where('agreement_number', 'NTHQA-BAD-001')->exists())->toBeFalse();
    });

    test('NTHQA-NFR-PART-011: partnership files keep strict types with pure entity boundaries', function (): void {
        $directory = new RecursiveDirectoryIterator(app_path('Modules/Partner/Domain/Partnership'));
        $files = [];
        foreach (new RecursiveIteratorIterator($directory) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            expect(str_contains($contents, 'declare(strict_types=1)'))->toBeTrue("missing strict types in {$file}");
        }

        expect((new ReflectionClass(PartnershipState::class))->isFinal())->toBeTrue();
        expect((new ReflectionClass(PartnershipState::class))->isReadOnly())->toBeTrue();
        expect((new ReflectionClass(PartnershipData::class))->isFinal())->toBeTrue();
        expect((new ReflectionClass(PartnershipData::class))->isReadOnly())->toBeTrue();
    });

    test('NTHQA-NFR-PART-012: partnership strings translate with mirrored catalogs', function (): void {
        $en = require lang_path('en/partnership.php');
        $id = require lang_path('id/partnership.php');

        expect($en)->not->toBeEmpty();
        expect(array_diff_key($en, $id))->toBe([]);

        app()->setLocale('en');
        expect(__('partnership.delete_blocked'))->not->toBe('partnership.delete_blocked');
        expect(__('partnership.terminate_blocked'))->not->toBe('partnership.terminate_blocked');

        app()->setLocale('id');
        expect(__('partnership.delete_blocked'))->not->toBe('partnership.delete_blocked');
        expect(__('partnership.terminate_blocked'))->not->toBe('partnership.terminate_blocked');
        expect(__('partnership.delete_blocked'))->not->toBe(__('partnership.terminate_blocked'));

        app()->setLocale('en');
        $englishLabel = PartnershipStatus::ACTIVE->label();
        app()->setLocale('id');
        $indonesianLabel = PartnershipStatus::ACTIVE->label();

        expect($englishLabel)->toBe('Active');
        expect($indonesianLabel)->toBe('Aktif');
    });
});

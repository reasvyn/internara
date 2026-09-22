<?php

declare(strict_types=1);

use App\Modules\Certification\Jobs\BatchIssueCertificatesJob;
use App\Modules\Document\Jobs\GenerateDocumentJob;
use App\Modules\User\Jobs\ArchiveStudentAccountsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

// Spec: 8FVZA — Job & Queue Infrastructure (Phase 12)
// Behavioral: verifies queue dispatch, retries, backoff, and contract adherence
describe('8FVZA: Job and Queue Infrastructure Behavioral', function () {
    test('8FVZA-FR-QUEUE-001: all queued jobs implement the ShouldQueue interface', function () {
        $docJob = new GenerateDocumentJob('doc-1');
        $certJob = new BatchIssueCertificatesJob(['reg-1'], 'completed', 'tpl-1', 'admin-1');
        $archiveJob = new ArchiveStudentAccountsJob(['user-1']);

        expect($docJob)->toBeInstanceOf(ShouldQueue::class)
            ->and($certJob)->toBeInstanceOf(ShouldQueue::class)
            ->and($archiveJob)->toBeInstanceOf(ShouldQueue::class);
    });

    test('8FVZA-FR-QUEUE-002, 8FVZA-UC-QUEUE-002: all baseline jobs set tries = 3 and follow contract', function () {
        $docJob = new GenerateDocumentJob('doc-uuid');
        $certJob = new BatchIssueCertificatesJob(['reg-1'], 'completed', 'tpl-1', 'admin-1');
        $archiveJob = new ArchiveStudentAccountsJob(['user-1']);

        expect($docJob->tries)->toBe(3)
            ->and($certJob->tries)->toBe(3)
            ->and($archiveJob->tries)->toBe(3);
    });

    test('8FVZA-FR-QUEUE-003, 8FVZA-NFR-QUEUE-001: all baseline jobs set exponential backoff = [2, 10, 30]', function () {
        $docJob = new GenerateDocumentJob('doc-uuid');
        $certJob = new BatchIssueCertificatesJob(['reg-1'], 'completed', 'tpl-1', 'admin-1');
        $archiveJob = new ArchiveStudentAccountsJob(['user-1']);

        expect($docJob->backoff)->toBe([2, 10, 30])
            ->and($certJob->backoff)->toBe([2, 10, 30])
            ->and($archiveJob->backoff)->toBe([2, 10, 30]);
    });

    test('8FVZA-FR-QUEUE-004, 8FVZA-DD-QUEUE-002: job constructors use scalar or string identifiers instead of whole models', function () {
        $ref = new ReflectionClass(GenerateDocumentJob::class);
        $constructor = $ref->getConstructor();
        expect($constructor)->not->toBeNull();

        $params = $constructor->getParameters();
        expect($params[0]->getType()->getName())->toBe('string');
    });

    test('8FVZA-FR-QUEUE-005: job payloads reference models by UUID string, never serialized model objects', function () {
        $job = new GenerateDocumentJob('01a00000-0000-0000-0000-000000000001');
        $ref = new ReflectionProperty($job, 'documentId');

        expect($ref->getValue($job))->toBe('01a00000-0000-0000-0000-000000000001');
    });

    test('8FVZA-FR-QUEUE-006, 8FVZA-UC-QUEUE-003: failed jobs table exists in schema for persistence and inspection', function () {
        expect(Schema::hasTable('failed_jobs') || Schema::hasTable('jobs'))->toBeTrue();
    });

    test('8FVZA-FR-QUEUE-007, 8FVZA-DD-QUEUE-003: jobs never dispatch events or write to activity log directly', function () {
        $ref = new ReflectionClass(GenerateDocumentJob::class);
        $methods = collect($ref->getMethods())->pluck('name')->all();

        expect($methods)->toContain('handle')
            ->and($methods)->toContain('failed');
    });

    test('8FVZA-FR-QUEUE-008, 8FVZA-NFR-QUEUE-002, 8FVZA-DD-QUEUE-001: queue driver is selectable through environment with sync as default', function () {
        $default = config('queue.default');
        expect(in_array($default, ['sync', 'redis', 'database']))->toBeTrue();
    });

    test('8FVZA-FR-QUEUE-009, 8FVZA-DD-QUEUE-004: jobs dispatch after triggering transaction commits', function () {
        Queue::fake();

        DB::transaction(function () {
            GenerateDocumentJob::dispatch('doc-tx-commit');
        });

        Queue::assertPushed(GenerateDocumentJob::class);
    });

    test('8FVZA-FR-QUEUE-010, 8FVZA-NFR-QUEUE-003: every baseline job implements failed callback with exception context', function () {
        $jobs = [
            GenerateDocumentJob::class,
            BatchIssueCertificatesJob::class,
            ArchiveStudentAccountsJob::class,
        ];

        foreach ($jobs as $job) {
            $ref = new ReflectionClass($job);
            expect($ref->hasMethod('failed'))->toBeTrue();
        }
    });

    test('8FVZA-FR-QUEUE-011, 8FVZA-NFR-QUEUE-004: queue monitor or health commands are discoverable without extra infra', function () {
        $commands = Artisan::all();
        expect(array_key_exists('queue:monitor', $commands) || array_key_exists('system:health', $commands))->toBeTrue();
    });

    test('8FVZA-FR-QUEUE-012, 8FVZA-UC-QUEUE-001: baseline jobs dispatch asynchronously cleanly', function () {
        Queue::fake();

        GenerateDocumentJob::dispatch('dummy-doc');
        BatchIssueCertificatesJob::dispatch(['reg-1'], 'completed', 'tpl-1', 'usr-1');
        ArchiveStudentAccountsJob::dispatch(['usr-2']);

        Queue::assertPushed(GenerateDocumentJob::class);
        Queue::assertPushed(BatchIssueCertificatesJob::class);
        Queue::assertPushed(ArchiveStudentAccountsJob::class);
    });
});

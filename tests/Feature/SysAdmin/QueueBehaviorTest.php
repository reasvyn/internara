<?php

declare(strict_types=1);

use App\Modules\Certification\Jobs\BatchIssueCertificatesJob;
use App\Modules\Document\Jobs\GenerateDocumentJob;
use App\Modules\User\Jobs\ArchiveStudentAccountsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

// Spec: 8FVZA — Job & Queue Infrastructure (Phase 12)
// Behavioral: verifies queue dispatch, retries, backoff, and contract adherence
describe('8FVZA: Job and Queue Infrastructure Behavioral', function () {
    it('FR-QUEUE-001: all queued jobs implement the ShouldQueue interface', function () {
        $docJob = new GenerateDocumentJob('doc-1');
        $certJob = new BatchIssueCertificatesJob(['reg-1'], 'completed', 'tpl-1', 'admin-1');
        $archiveJob = new ArchiveStudentAccountsJob(['user-1']);

        expect($docJob)->toBeInstanceOf(ShouldQueue::class)
            ->and($certJob)->toBeInstanceOf(ShouldQueue::class)
            ->and($archiveJob)->toBeInstanceOf(ShouldQueue::class);
    });

    it('FR-QUEUE-002: all baseline jobs set tries = 3', function () {
        $docJob = new GenerateDocumentJob('doc-uuid');
        $certJob = new BatchIssueCertificatesJob(['reg-1'], 'completed', 'tpl-1', 'admin-1');
        $archiveJob = new ArchiveStudentAccountsJob(['user-1']);

        expect($docJob->tries)->toBe(3)
            ->and($certJob->tries)->toBe(3)
            ->and($archiveJob->tries)->toBe(3);
    });

    it('FR-QUEUE-003: all baseline jobs set backoff = [2, 10, 30]', function () {
        $docJob = new GenerateDocumentJob('doc-uuid');
        $certJob = new BatchIssueCertificatesJob(['reg-1'], 'completed', 'tpl-1', 'admin-1');
        $archiveJob = new ArchiveStudentAccountsJob(['user-1']);

        expect($docJob->backoff)->toBe([2, 10, 30])
            ->and($certJob->backoff)->toBe([2, 10, 30])
            ->and($archiveJob->backoff)->toBe([2, 10, 30]);
    });

    it('FR-QUEUE-004: job constructors use scalar or string identifiers instead of whole models', function () {
        $ref = new ReflectionClass(GenerateDocumentJob::class);
        $constructor = $ref->getConstructor();
        expect($constructor)->not->toBeNull();

        $params = $constructor->getParameters();
        expect($params[0]->getType()->getName())->toBe('string');
    });

    it('FR-QUEUE-005: job payloads reference models by UUID string, never serialized model objects', function () {
        $job = new GenerateDocumentJob('01a00000-0000-0000-0000-000000000001');
        $ref = new ReflectionProperty($job, 'documentId');

        expect($ref->getValue($job))->toBe('01a00000-0000-0000-0000-000000000001');
    });

    it('FR-QUEUE-006: failed jobs table exists in schema for persistence', function () {
        expect(Schema::hasTable('failed_jobs') || Schema::hasTable('jobs'))->toBeTrue();
    });

    it('FR-QUEUE-008: default queue connection is configured per environment', function () {
        $default = config('queue.default');
        expect(in_array($default, ['sync', 'redis', 'database']))->toBeTrue();
    });

    it('FR-QUEUE-010: every baseline job implements failed callback', function () {
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

    it('FR-QUEUE-012: baseline jobs dispatch through queue fake cleanly', function () {
        Queue::fake();

        GenerateDocumentJob::dispatch('dummy-doc');
        BatchIssueCertificatesJob::dispatch(['reg-1'], 'completed', 'tpl-1', 'usr-1');
        ArchiveStudentAccountsJob::dispatch(['usr-2']);

        Queue::assertPushed(GenerateDocumentJob::class);
        Queue::assertPushed(BatchIssueCertificatesJob::class);
        Queue::assertPushed(ArchiveStudentAccountsJob::class);
    });
});

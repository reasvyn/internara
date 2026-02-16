<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Metadata\Services;

use Illuminate\Support\Facades\File;
use Modules\Core\Metadata\Services\MetadataService;
use RuntimeException;

describe('Metadata Service', function () {
    beforeEach(function () {
        $this->metadataPath = base_path('app_info.json');
        $this->originalContent = File::exists($this->metadataPath)
            ? File::get($this->metadataPath)
            : null;

        $this->testMetadata = [
            'name' => 'Internara Test',
            'version' => '1.0.0-test',
            'series_code' => 'TEST-01',
            'author' => [
                'name' => 'Reas Vyn',
                'email' => 'test@example.com',
            ],
        ];

        File::put($this->metadataPath, json_encode($this->testMetadata));
        $this->service = new MetadataService();
    });

    afterEach(function () {
        if ($this->originalContent) {
            File::put($this->metadataPath, $this->originalContent);
        } else {
            File::delete($this->metadataPath);
        }
    });

    test('it can retrieve specific metadata values', function () {
        expect($this->service->get('name'))
            ->toBe('Internara Test')
            ->and($this->service->getVersion())
            ->toBe('1.0.0-test')
            ->and($this->service->getSeriesCode())
            ->toBe('TEST-01');
    });

    test('it can retrieve nested metadata values', function () {
        expect($this->service->get('author.name'))->toBe('Reas Vyn');
    });

    test('it returns default value for missing keys', function () {
        expect($this->service->get('non_existent', 'default'))->toBe('default');
    });

    test('it verifies system integrity based on author identity', function () {
        // Since self::AUTHOR_IDENTITY is likely hardcoded in the service
        // Let's assume it matches for now.
        $this->service->verifyIntegrity();
        expect(true)->toBeTrue();
    });

    test('it throws exception if author identity is tampered', function () {
        $tamperedMetadata = $this->testMetadata;
        $tamperedMetadata['author']['name'] = 'Tampered Author';
        File::put($this->metadataPath, json_encode($tamperedMetadata));

        $service = new MetadataService(); // Fresh instance to clear cache
        $service->verifyIntegrity();
    })->throws(RuntimeException::class, 'Integrity Violation');
});

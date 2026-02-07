<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Modules\Core\Services\MetadataService;

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
    $this->service = new MetadataService;
});

afterEach(function () {
    if ($this->originalContent) {
        File::put($this->metadataPath, $this->originalContent);
    } else {
        File::delete($this->metadataPath);
    }
});

test('it can retrieve specific metadata keys', function () {
    expect($this->service->get('name'))
        ->toBe('Internara Test')
        ->and($this->service->getVersion())
        ->toBe('1.0.0-test')
        ->and($this->service->getSeriesCode())
        ->toBe('TEST-01');
});

test('it can retrieve nested metadata', function () {
    expect($this->service->get('author.name'))->toBe('Reas Vyn');
});

test('it returns default value if key missing', function () {
    expect($this->service->get('non_existent', 'default'))->toBe('default');
});

test('it verifies integrity successfully with correct author', function () {
    $this->service->verifyIntegrity();
    expect(true)->toBeTrue(); // No exception thrown
});

test('it throws exception if author is tampered', function () {
    $tamperedMetadata = $this->testMetadata;
    $tamperedMetadata['author']['name'] = 'Unknown Hacker';
    File::put($this->metadataPath, json_encode($tamperedMetadata));

    // Clear cache in service
    $service = new MetadataService;

    $service->verifyIntegrity();
})->throws(
    RuntimeException::class,
    'Integrity Violation: Unauthorized author detected [Unknown Hacker]',
);

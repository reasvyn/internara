<?php

declare(strict_types=1);

use App\Core\Support\AppIntegrity;
use Illuminate\Support\Facades\File;

test('verify runs without exception in testing environment', function () {
    expect(AppIntegrity::verify())->toBeNull();
});

test('verify handles missing composer.json gracefully in testing', function () {
    $originalPath = base_path('composer.json');
    $tempPath = sys_get_temp_dir().'/composer_test_'.uniqid().'.json';

    File::move($originalPath, $tempPath);

    try {
        // In testing, it should not throw, just return null (after logging)
        expect(AppIntegrity::verify())->toBeNull();
    } finally {
        File::move($tempPath, $originalPath);
    }
});

test('verify handles invalid JSON gracefully in testing', function () {
    $originalPath = base_path('composer.json');
    $tempPath = sys_get_temp_dir().'/composer_test_'.uniqid().'.json';

    File::move($originalPath, $tempPath);
    File::put($originalPath, '{ invalid json }');

    try {
        // In testing, it should not throw
        expect(AppIntegrity::verify())->toBeNull();
    } finally {
        File::delete($originalPath);
        File::move($tempPath, $originalPath);
    }
});

test('verify handles author mismatch gracefully in testing', function () {
    $originalPath = base_path('composer.json');
    $content = File::get($originalPath);
    $data = json_decode($content, true);

    $originalAuthor = $data['authors'][0]['name'] ?? '';
    $data['authors'][0]['name'] = 'Different Author';
    File::put($originalPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    try {
        // In testing, it should not throw
        expect(AppIntegrity::verify())->toBeNull();
    } finally {
        $data['authors'][0]['name'] = $originalAuthor;
        File::put($originalPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
});

test('verify passes silently with correct author', function () {
    expect(AppIntegrity::verify())->toBeNull();
});

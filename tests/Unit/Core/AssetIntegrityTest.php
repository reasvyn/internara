<?php

declare(strict_types=1);

describe('C8F0D: frontend asset integrity', function (): void {
    test('C8F0D-FR-UTIL-005: livewire published assets match installed vendor distribution', function (): void {
        $vendorManifestPath = base_path('vendor/livewire/livewire/dist/manifest.json');
        if (! file_exists($vendorManifestPath)) {
            $this->markTestSkipped('Livewire vendor distribution manifest not found.');
        }

        $publicManifestPath = public_path('vendor/livewire/manifest.json');
        expect(file_exists($publicManifestPath))->toBeTrue(
            'Livewire public manifest missing. Run "php artisan livewire:publish --assets" to publish assets.'
        );

        $vendorManifest = json_decode((string) file_get_contents($vendorManifestPath), true);
        $publicManifest = json_decode((string) file_get_contents($publicManifestPath), true);

        expect($publicManifest)->toBe(
            $vendorManifest,
            'Livewire published assets are out of date with vendor/livewire/livewire. Run "php artisan livewire:publish --assets" to synchronize.'
        );

        expect(file_exists(public_path('vendor/livewire/livewire.min.js')))->toBeTrue();
        expect(filesize(public_path('vendor/livewire/livewire.min.js')))->toBeGreaterThan(0);
    });

    test('C8F0D-FR-UTIL-006: compiled vite assets in manifest exist on disk', function (): void {
        $manifestPath = public_path('build/manifest.json');
        if (! file_exists($manifestPath)) {
            $this->markTestSkipped('Vite build manifest not present in this environment.');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        expect($manifest)->toBeArray();

        foreach ($manifest as $entry) {
            if (isset($entry['file'])) {
                $assetPath = public_path('build/'.$entry['file']);
                expect(file_exists($assetPath))->toBeTrue(
                    "Asset file '{$entry['file']}' defined in manifest.json does not exist on disk."
                );
            }
        }
    });
});

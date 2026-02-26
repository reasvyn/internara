<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Metadata\Services;

use Illuminate\Support\Facades\File;
use Modules\Core\Metadata\Services\MetadataService;

describe('Metadata Service', function () {
    beforeEach(function () {
        $this->path = base_path('app_info.json');
        $this->original = \Illuminate\Support\Facades\File::exists($this->path) ? \Illuminate\Support\Facades\File::get($this->path) : null;
        $this->service = new MetadataService;
        \Modules\Shared\Support\AppInfo::clearCache();
    });

    afterEach(function () {
        if ($this->original) {
            \Illuminate\Support\Facades\File::put($this->path, $this->original);
        } else {
            \Illuminate\Support\Facades\File::delete($this->path);
        }
        \Modules\Shared\Support\AppInfo::clearCache();
    });

    test('test retrieves product identity accurately', function () {
        $data = ['name' => 'Internara', 'version' => '1.0.0'];
        File::put($this->path, json_encode($data));

        expect($this->service->get('name'))->toBe('Internara')
            ->and($this->service->getVersion())->toBe('1.0.0');
    });

    test('test returns fallback for missing metadata', function () {
        File::put($this->path, json_encode([]));
        expect($this->service->get('nonexistent', 'fallback'))->toBe('fallback');
    });

    test('test validates system integrity via author attribution', function () {
        $data = ['author' => ['name' => 'Reas Vyn']];
        File::put($this->path, json_encode($data));

        // It should pass without exception if author is correct
        $this->service->verifyIntegrity();
        expect(true)->toBeTrue();

        // It should throw exception if author is modified
        $data = ['author' => ['name' => 'Unauthorized User']];
        File::put($this->path, json_encode($data));

        $this->service->verifyIntegrity();
    })->throws(\RuntimeException::class);
});

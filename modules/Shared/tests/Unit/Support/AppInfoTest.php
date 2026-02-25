<?php

declare(strict_types=1);

namespace Modules\Shared\Tests\Unit\Support;

use Illuminate\Support\Facades\File;
use Modules\Shared\Support\AppInfo;

describe('AppInfo Support Utility', function () {
    beforeEach(function () {
        $this->path = base_path('app_info.json');
        $this->originalContent = File::exists($this->path) ? File::get($this->path) : null;
        AppInfo::clearCache();
    });

    afterEach(function () {
        if ($this->originalContent) {
            File::put($this->path, $this->originalContent);
        } else {
            File::delete($this->path);
        }
        AppInfo::clearCache();
    });

    test('it can retrieve all metadata', function () {
        $data = ['name' => 'Test App', 'version' => '1.2.3'];
        File::put($this->path, json_encode($data));
        
        expect(AppInfo::all())->toBe($data);
    });

    test('it can retrieve specific key with dot notation', function () {
        $data = ['author' => ['name' => 'Reas']];
        File::put($this->path, json_encode($data));
        
        expect(AppInfo::get('author.name'))->toBe('Reas');
    });

    test('it returns default value for missing keys', function () {
        File::put($this->path, json_encode([]));
        
        expect(AppInfo::get('missing', 'fallback'))->toBe('default');
    });

    test('it provides helper for version', function () {
        File::put($this->path, json_encode(['version' => '2.0.0']));
        
        expect(AppInfo::version())->toBe('2.0.0');
    });

    test('it handles missing file gracefully', function () {
        if (File::exists($this->path)) {
            File::delete($this->path);
        }
        
        expect(AppInfo::all())->toBe([]);
    });
});

<?php

declare(strict_types=1);

use App\Support\AppInfo;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    AppInfo::clearCache();
});

describe('composer.json SSoT', function () {
    it('reads all metadata from composer.json', function () {
        $info = AppInfo::all();

        expect($info)->toBeArray()
            ->toHaveKey('name')
            ->toHaveKey('version')
            ->toHaveKey('description')
            ->toHaveKey('license')
            ->toHaveKey('author')
            ->toHaveKey('support');
        expect($info['name'])->toBe('Internara');
        expect($info['version'])->toBe('0.1.0');
        expect($info['description'])->toContain('field work management system');
        expect($info['license'])->toBe('MIT');
    });

    it('reads author info from composer.json', function () {
        $info = AppInfo::all();
        $author = $info['author'];

        expect($author)->toHaveKey('name');
        expect($author)->toHaveKey('email');
        expect($author['name'])->toBe('Reas Vyn');
        expect($author['email'])->toBe('reasvyn@gmail.com');
    });

    it('maps homepage to github for author', function () {
        $info = AppInfo::all();
        $author = $info['author'];

        expect($author)->toHaveKey('github');
        expect($author['github'])->toBe('https://github.com/reasvyn');
    });

    it('reads support info from composer.json', function () {
        $info = AppInfo::all();

        expect($info['support'])->toBeArray();
        expect($info['support'])->toHaveKey('email');
    });

    it('returns default name when composer.json display_name is missing', function () {
        $backup = base_path('composer.json');
        $content = File::get($backup);
        $data = json_decode($content, true);
        unset($data['display_name']);
        File::put($backup, json_encode($data));

        AppInfo::clearCache();
        $name = AppInfo::get('name');

        expect($name)->toBe('reasvyn/internara');

        File::put($backup, $content);
    });
});

describe('get', function () {
    it('returns value for a specific key', function () {
        $name = AppInfo::get('name');

        expect($name)->toBe('Internara');
    });

    it('returns default for missing key', function () {
        $value = AppInfo::get('non_existent_key', 'default_value');

        expect($value)->toBe('default_value');
    });

    it('returns null default for missing key when no default provided', function () {
        $value = AppInfo::get('non_existent_key');

        expect($value)->toBeNull();
    });

    it('uses dot notation for nested keys', function () {
        $authorName = AppInfo::get('author.name');

        expect($authorName)->toBe('Reas Vyn');
    });
});

describe('version', function () {
    it('returns version from composer.json', function () {
        $version = AppInfo::version();

        expect($version)->toBe('0.1.0');
    });

    it('returns string type', function () {
        $version = AppInfo::version();

        expect($version)->toBeString();
    });
});

describe('author', function () {
    it('returns author array from composer.json', function () {
        $author = AppInfo::author();

        expect($author)->toBeArray();
        expect($author)->toHaveKeys(['name', 'email', 'github']);
    });
});

describe('logo', function () {
    it('returns default logo URL', function () {
        $logo = AppInfo::logo();

        expect($logo)->toBeString();
        expect($logo)->toContain('logo.png');
    });
});

describe('caching', function () {
    it('caches results after first call', function () {
        $first = AppInfo::all();
        $second = AppInfo::all();

        expect($first)->toBe($second);
    });

    it('can clear cache', function () {
        AppInfo::all();
        AppInfo::clearCache();

        $info = AppInfo::all();
        expect($info)->toBeArray();
        expect($info['name'])->toBe('Internara');
    });
});

describe('error handling', function () {
    it('returns empty array when composer.json is missing', function () {
        $path = base_path('composer.json');
        $backup = base_path('composer.json.bak');
        File::move($path, $backup);

        AppInfo::clearCache();
        $info = AppInfo::all();

        expect($info)->toBe([]);

        File::move($backup, $path);
    });

    it('returns empty array when composer.json contains invalid JSON', function () {
        $path = base_path('composer.json');
        $backup = base_path('composer.json.bak');
        File::move($path, $backup);
        File::put($path, '{invalid json}');

        AppInfo::clearCache();
        $info = AppInfo::all();

        expect($info)->toBe([]);

        File::delete($path);
        File::move($backup, $path);
    });
});

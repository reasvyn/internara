<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Metadata\Services;

use Illuminate\Support\Facades\File;
use Modules\Core\Metadata\Services\MetadataService;
use Modules\Shared\Support\AppInfo;

describe('MetadataService', function () {
    beforeEach(function () {
        $this->path = base_path('app_info.json');
        $this->original = File::exists($this->path) ? File::get($this->path) : null;
        $this->service = new MetadataService();
        AppInfo::clearCache();
    });

    afterEach(function () {
        if ($this->original) {
            File::put($this->path, $this->original);
        } else {
            File::delete($this->path);
        }
        AppInfo::clearCache();
    });

    test('it retrieves product identity accurately', function () {
        $data = ['name' => 'Internara', 'version' => '1.0.0'];
        File::put($this->path, json_encode($data));

        expect($this->service->get('name'))
            ->toBe('Internara')
            ->and($this->service->getVersion())
            ->toBe('1.0.0');
    });

    test('it returns fallback for missing metadata', function () {
        File::put($this->path, json_encode([]));
        expect($this->service->get('nonexistent', 'fallback'))->toBe('fallback');
    });

    test('it returns all metadata', function () {
        $data = [
            'name' => 'Internara',
            'version' => '1.0.0',
            'author' => ['name' => 'Reas Vyn'],
        ];
        File::put($this->path, json_encode($data));

        $result = $this->service->getAll();
        expect($result)
            ->toHaveKey('name', 'Internara')
            ->toHaveKey('version', '1.0.0')
            ->toHaveKey('author');
    });

    test('it returns app name', function () {
        $data = ['name' => 'Internara'];
        File::put($this->path, json_encode($data));

        expect($this->service->getAppName())->toBe('Internara');
    });

    test('it returns author info', function () {
        $data = ['author' => ['name' => 'Reas Vyn', 'email' => 'dev@internara.test']];
        File::put($this->path, json_encode($data));

        $author = $this->service->getAuthor();
        expect($author)
            ->toHaveKey('name', 'Reas Vyn')
            ->toHaveKey('email', 'dev@internara.test');
    });

    test('it does not throw when author is correct', function () {
        $data = ['author' => ['name' => 'Reas Vyn']];
        File::put($this->path, json_encode($data));

        expect(fn () => $this->service->verifyIntegrity())
            ->not()->toThrow(\RuntimeException::class);
    });

    test('it throws RuntimeException when author is unauthorized', function () {
        $data = ['author' => ['name' => 'Unauthorized User']];
        File::put($this->path, json_encode($data));

        expect(fn () => $this->service->verifyIntegrity())
            ->toThrow(\RuntimeException::class);
    });
});
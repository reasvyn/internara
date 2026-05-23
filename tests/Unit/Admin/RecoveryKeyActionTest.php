<?php

declare(strict_types=1);

use App\Domain\Admin\Actions\ReadRecoveryKeyAction;
use App\Domain\Admin\Actions\SaveRecoveryKeyAction;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->key = Str::random(64);
    $this->filePath = storage_path('app/private/.recovery-key');
    $this->dirPath = storage_path('app/private');
});

afterEach(function () {
    if (File::exists($this->filePath)) {
        File::delete($this->filePath);
    }
});

describe('SaveRecoveryKeyAction', function () {
    it('saves the key to the correct file path', function () {
        $action = app(SaveRecoveryKeyAction::class);

        $path = $action->execute($this->key);

        expect($path)->toBe($this->filePath);
        expect(File::exists($path))->toBeTrue();
    });

    it('creates the directory if it does not exist', function () {
        File::deleteDirectory($this->dirPath);
        expect(File::exists($this->dirPath))->toBeFalse();

        $action = app(SaveRecoveryKeyAction::class);

        $action->execute($this->key);

        expect(File::exists($this->dirPath))->toBeTrue();
        expect(File::isDirectory($this->dirPath))->toBeTrue();

        File::cleanDirectory($this->dirPath);
        File::deleteDirectory($this->dirPath);
    });

    it('writes the key as the last non-comment line', function () {
        $action = app(SaveRecoveryKeyAction::class);

        $action->execute($this->key);

        $content = File::get($this->filePath);
        $lines = array_filter(explode(PHP_EOL, $content), fn ($line) => trim($line) !== '');
        $lastLine = array_values(array_slice($lines, -1))[0] ?? '';

        expect($lastLine)->toBe($this->key);
    });

    it('includes a header comment', function () {
        $action = app(SaveRecoveryKeyAction::class);

        $action->execute($this->key);

        $content = File::get($this->filePath);

        expect($content)->toContain('# INTERNARA RECOVERY KEY');
        expect($content)->toContain('# This key grants super admin access');
    });

    it('sets file permissions to 0600', function () {
        $action = app(SaveRecoveryKeyAction::class);

        $action->execute($this->key);

        $perms = File::chmod($this->filePath);

        expect($perms)->toBe('0600');
    });

    it('overwrites an existing file', function () {
        File::put($this->filePath, 'old-key-content');

        $action = app(SaveRecoveryKeyAction::class);

        $action->execute($this->key);

        $content = File::get($this->filePath);
        $lines = array_filter(explode(PHP_EOL, $content), fn ($line) => trim($line) !== '');
        $lastLine = array_values(array_slice($lines, -1))[0] ?? '';

        expect($lastLine)->toBe($this->key);
        expect($content)->not->toContain('old-key-content');
    });
});

describe('ReadRecoveryKeyAction', function () {
    it('returns the key from the file', function () {
        File::ensureDirectoryExists($this->dirPath);
        File::put($this->filePath, "# HEADER\n{$this->key}\n");

        $action = app(ReadRecoveryKeyAction::class);

        $result = $action->execute();

        expect($result)->toBe($this->key);
    });

    it('skips comment lines and returns only the key', function () {
        File::ensureDirectoryExists($this->dirPath);
        $content = '# INTERNARA RECOVERY KEY'.PHP_EOL
            .'# This key grants super admin access.'.PHP_EOL
            .PHP_EOL
            .$this->key.PHP_EOL;
        File::put($this->filePath, $content);

        $action = app(ReadRecoveryKeyAction::class);

        $result = $action->execute();

        expect($result)->toBe($this->key);
    });

    it('returns null when the file does not exist', function () {
        File::delete($this->filePath);

        $action = app(ReadRecoveryKeyAction::class);

        $result = $action->execute();

        expect($result)->toBeNull();
    });

    it('returns null when the file is empty', function () {
        File::ensureDirectoryExists($this->dirPath);
        File::put($this->filePath, '');

        $action = app(ReadRecoveryKeyAction::class);

        $result = $action->execute();

        expect($result)->toBeNull();
    });

    it('returns null when the file has only comments', function () {
        File::ensureDirectoryExists($this->dirPath);
        File::put($this->filePath, "# Only comments\n# No actual key\n");

        $action = app(ReadRecoveryKeyAction::class);

        $result = $action->execute();

        expect($result)->toBeNull();
    });

    it('trims whitespace from the key', function () {
        File::ensureDirectoryExists($this->dirPath);
        File::put($this->filePath, "# HEADER\n  {$this->key}  \n");

        $action = app(ReadRecoveryKeyAction::class);

        $result = $action->execute();

        expect($result)->toBe($this->key);
    });
});

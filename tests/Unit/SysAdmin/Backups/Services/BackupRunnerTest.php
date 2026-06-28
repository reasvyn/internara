<?php

declare(strict_types=1);

use App\SysAdmin\Backups\Services\BackupRunner;

beforeEach(function () {
    $this->runner = new BackupRunner;
});

test('delete file returns false for non-existent file', function () {
    expect($this->runner->deleteFile('/nonexistent/path/file.sql'))->toBeFalse();
});

test('file size returns 0 for non-existent file', function () {
    expect($this->runner->fileSize('/nonexistent/path/file.sql'))->toBe(0);
});

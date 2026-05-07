<?php

declare(strict_types=1);

use App\Support\Integrity;

beforeEach(function () {
    $ref = new ReflectionMethod(Integrity::class, 'verifyComposerFile');
    $ref->setAccessible(true);

    $this->verify = $ref;
});

it('passes for valid composer.json', function () {
    $this->verify->invoke(null, base_path('composer.json'));

    expect(true)->toBeTrue();
});

it('fails when composer.json is missing', function () {
    expect(fn () => $this->verify->invoke(null, '/nonexistent/path/composer.json'))
        ->toThrow(RuntimeException::class, 'missing');
});

it('fails when composer.json has invalid JSON', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'integrity_test_');
    file_put_contents($tmp, '{invalid json}');

    expect(fn () => $this->verify->invoke(null, $tmp))
        ->toThrow(RuntimeException::class, 'invalid JSON');

    unlink($tmp);
});

it('fails when composer.json has wrong author', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'integrity_test_');
    file_put_contents($tmp, json_encode(['authors' => [['name' => 'Hacker']]]));

    expect(fn () => $this->verify->invoke(null, $tmp))
        ->toThrow(RuntimeException::class, 'Unauthorized author');

    unlink($tmp);
});

it('does not verify in testing environment', function () {
    Integrity::verify();

    expect(true)->toBeTrue();
});

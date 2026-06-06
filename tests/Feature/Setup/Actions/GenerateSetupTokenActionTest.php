<?php

declare(strict_types=1);

namespace Tests\Feature\Setup;

use App\Setup\Actions\GenerateSetupTokenAction;
use App\SysAdmin\Settings\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

test('generate setup token action creates token and increments version', function () {
    $action = new GenerateSetupTokenAction;

    $result = $action->execute();

    expect($result['plaintext'])->not->toBeEmpty();
    expect($result['expires_at'])->not->toBeNull();

    $token = Settings::get('setup.install_token');
    expect($token)->not->toBeNull();
    expect(Crypt::decryptString($token))->toBe($result['plaintext']);
    expect(Settings::get('setup.token_version'))->toBe(1);
});

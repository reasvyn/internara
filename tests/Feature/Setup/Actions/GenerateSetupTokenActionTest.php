<?php

declare(strict_types=1);

namespace Tests\Feature\Setup;

use App\Setup\Actions\GenerateSetupTokenAction;
use App\Setup\Models\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

test('generate setup token action creates token and increments version', function () {
    $action = new GenerateSetupTokenAction;

    $result = $action->execute();

    expect($result['plaintext'])->not->toBeEmpty();
    expect($result['expires_at'])->not->toBeNull();

    $setup = Setup::first();
    expect($setup)->not->toBeNull();
    expect(Crypt::decryptString($setup->setup_token))->toBe($result['plaintext']);
    expect($setup->token_version)->toBe(1);
});

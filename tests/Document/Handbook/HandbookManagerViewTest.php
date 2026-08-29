<?php

declare(strict_types=1);

use App\Modules\Document\Domain\Handbook\Livewire\HandbookManager;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('ZUFG8-FR-HM1: handbook manager page renders with valid guide include', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    test()->actingAs($admin);

    $html = Livewire::test(HandbookManager::class)
        ->html();

    expect($html)->not->toContain('View [handbook.handbook.components.handbook-guide] not found');
});

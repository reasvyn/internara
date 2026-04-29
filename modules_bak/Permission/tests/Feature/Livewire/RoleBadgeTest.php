<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Permission\Livewire\RoleBadge;

test('it renders localized role name', function () {
    app()->setLocale('id');
    Livewire::test(RoleBadge::class, ['role' => 'student'])->assertSee('Siswa');

    app()->setLocale('en');
    Livewire::test(RoleBadge::class, ['role' => 'student'])->assertSee('Student');
});

test('it uses correct color for roles', function () {
    Livewire::test(RoleBadge::class, ['role' => 'super-admin'])->assertSee('badge-error');

    Livewire::test(RoleBadge::class, ['role' => 'admin'])->assertSee('badge-primary');
});

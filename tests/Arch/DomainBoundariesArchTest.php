<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Core\Models\BaseModel;

arch('Core does not import business domains')
    ->expect(BaseModel::class)
    ->not->toUse('Livewire');

arch('Auth enums do not import Livewire')
    ->expect(AccountStatus::class)
    ->not->toUse('Livewire');

arch('Auth enums do not import Eloquent')
    ->expect(Role::class)
    ->not->toUse('Illuminate\Database');

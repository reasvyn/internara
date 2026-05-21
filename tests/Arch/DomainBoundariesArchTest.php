<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;

arch('Core does not import any business domain')
    ->expect('App\Domain\Core')
    ->not->toUse('App\Domain\Admin')
    ->not->toUse('App\Domain\Assessment')
    ->not->toUse('App\Domain\Assignment')
    ->not->toUse('App\Domain\Attendance')
    ->not->toUse('App\Domain\Auth')
    ->not->toUse('App\Domain\Certificate')
    ->not->toUse('App\Domain\Document')
    ->not->toUse('App\Domain\Evaluation')
    ->not->toUse('App\Domain\Guidance')
    ->not->toUse('App\Domain\Incident')
    ->not->toUse('App\Domain\Internship')
    ->not->toUse('App\Domain\Logbook')
    ->not->toUse('App\Domain\Mentee')
    ->not->toUse('App\Domain\Mentor')
    ->not->toUse('App\Domain\Partnership')
    ->not->toUse('App\Domain\Placement')
    ->not->toUse('App\Domain\Registration')
    ->not->toUse('App\Domain\Schedule')
    ->not->toUse('App\Domain\School')
    ->not->toUse('App\Domain\Settings')
    ->not->toUse('App\Domain\Setup')
    ->not->toUse('App\Domain\Shared')
    ->not->toUse('App\Domain\User');

arch('Auth enums do not import Livewire')
    ->expect(AccountStatus::class)
    ->not->toUse('Livewire');

arch('Auth enums do not import Eloquent')
    ->expect(Role::class)
    ->not->toUse('Illuminate\Database');

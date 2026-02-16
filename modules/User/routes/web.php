<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Livewire\Form;
use Modules\User\Livewire\Index;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('users')
        ->name('users.')
        ->group(function () {
            Route::get('/', Index::class)->name('index')->middleware('permission:user.view');
            Route::get('/create', Form::class)
                ->name('create')
                ->middleware('permission:user.create');
            Route::get('/{id}/edit', Form::class)
                ->name('edit')
                ->middleware('permission:user.update');
        });
});

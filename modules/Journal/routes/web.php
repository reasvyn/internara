<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Journal\Livewire\JournalIndex;
use Modules\Journal\Livewire\JournalEntryManager;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/journal', JournalIndex::class)->name('journal.index');
    Route::get('/journal/create', JournalEntryManager::class)->name('journal.create')->middleware('role:student');
    Route::get('/journal/{id}/edit', JournalEntryManager::class)->name('journal.edit')->middleware('role:student');
});

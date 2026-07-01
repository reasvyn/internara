<?php

declare(strict_types=1);

use App\Journals\Logbook\Livewire\LogbookEntry;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    test()->actingAs($student);
});

test('renders the logbook entry component', function () {
    Livewire::test(LogbookEntry::class)
        ->assertSuccessful();
});

test('defaults date to today', function () {
    Livewire::test(LogbookEntry::class)
        ->assertSet('date', now()->toDateString());
});

test('create opens modal', function () {
    Livewire::test(LogbookEntry::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->assertSet('date', now()->toDateString());
});

test('validates date and content are required', function () {
    Livewire::test(LogbookEntry::class)
        ->set('date', '')
        ->set('content', '')
        ->call('save')
        ->assertHasErrors(['date', 'content']);
});

test('validates content minimum length', function () {
    Livewire::test(LogbookEntry::class)
        ->set('date', now()->toDateString())
        ->set('content', 'Short')
        ->call('save')
        ->assertHasErrors(['content']);
});

test('allows optional learning outcomes', function () {
    Livewire::test(LogbookEntry::class)
        ->set('date', now()->toDateString())
        ->set('content', 'Test content for the journal entry')
        ->set('learning_outcomes', '')
        ->call('save')
        ->assertHasNoErrors(['learning_outcomes']);
});

test('remove photo reduces photo array', function () {
    Livewire::test(LogbookEntry::class)
        ->set('photos', [UploadedFile::fake()->image('photo.jpg')])
        ->call('removePhoto', 0)
        ->assertSet('photos', []);
});

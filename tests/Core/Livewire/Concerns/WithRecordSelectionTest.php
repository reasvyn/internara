<?php

declare(strict_types=1);

use App\Core\Livewire\Concerns\WithRecordSelection;
use Livewire\Component;

beforeEach(function () {
    $this->component = new class extends Component
    {
        use WithRecordSelection;

        public array $selectedIds = [];

        public function render(): string
        {
            return '';
        }
    };
});

test('with record selection starts empty', function () {
    expect($this->component->selectedIds)->toBe([]);
});

test('with record selection selects all', function () {
    $this->component->selectAll([1, 2, 3]);

    expect($this->component->selectedIds)->toBe([1, 2, 3]);
});

test('with record selection selects string uuids', function () {
    $uuids = ['550e8400-e29b-41d4-a716-446655440000', '6ba7b810-9dad-11d1-80b4-00c04fd430c8'];
    $this->component->selectAll($uuids);

    expect($this->component->selectedIds)->toBe($uuids);
});

test('with record selection clears selection', function () {
    $this->component->selectAll([1, 2, 3]);
    $this->component->clearSelection();

    expect($this->component->selectedIds)->toBe([]);
});

test('with record selection computes selected count', function () {
    $this->component->selectAll([1, 2, 3]);

    expect($this->component->selected_count)->toBe(3);
});

test('with record selection selected count is zero when empty', function () {
    expect($this->component->selected_count)->toBe(0);
});

test('with record selection overwrites previous selection', function () {
    $this->component->selectAll([1, 2]);
    $this->component->selectAll([3, 4, 5]);

    expect($this->component->selectedIds)->toBe([3, 4, 5]);
    expect($this->component->selected_count)->toBe(3);
});

test('with record selection handles empty array', function () {
    $this->component->selectAll([]);

    expect($this->component->selectedIds)->toBe([]);
    expect($this->component->selected_count)->toBe(0);
});

test('with record selection clearing after select sets to empty', function () {
    $this->component->selectAll([10, 20, 30]);
    $this->component->clearSelection();

    expect($this->component->selectedIds)->toBe([]);
    expect($this->component->selected_count)->toBe(0);
});

test('with record selection multiple clear calls are idempotent', function () {
    $this->component->clearSelection();

    expect($this->component->selectedIds)->toBe([]);
});

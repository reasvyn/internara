<?php

declare(strict_types=1);

use App\Livewire\Concerns\WithRecordSelection;
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

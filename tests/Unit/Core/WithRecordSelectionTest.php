<?php

declare(strict_types=1);

use App\Domain\Core\Livewire\Concerns\WithRecordSelection;
use Livewire\Component;

class TestWithRecordSelectionComponent extends Component
{
    use WithRecordSelection;

    public function render() {}
}

describe('WithRecordSelection', function () {
    it('starts with empty selection', function () {
        $component = new TestWithRecordSelectionComponent;

        expect($component->selectedIds)->toBe([]);
    });

    it('clears selection', function () {
        $component = new TestWithRecordSelectionComponent;
        $component->selectedIds = ['1', '2', '3'];

        $component->clearSelection();

        expect($component->selectedIds)->toBe([]);
    });

    it('selects all given ids', function () {
        $component = new TestWithRecordSelectionComponent;

        $component->selectAll(['a', 'b', 'c']);

        expect($component->selectedIds)->toBe(['a', 'b', 'c']);
    });

    it('returns selected count', function () {
        $component = new TestWithRecordSelectionComponent;
        $component->selectedIds = ['x', 'y'];

        expect($component->selected_count)->toBe(2);
    });

    it('returns zero count when nothing selected', function () {
        $component = new TestWithRecordSelectionComponent;

        expect($component->selected_count)->toBe(0);
    });
});

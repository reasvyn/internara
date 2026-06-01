<?php

declare(strict_types=1);

use App\Domain\Core\Livewire\Concerns\WithRecordSelection;
use Livewire\Component;

class TestSelectionComponent extends Component
{
    use WithRecordSelection;

    public function render() {}
}

describe('WithRecordSelection', function () {
    it('starts with empty selection', function () {
        expect((new TestSelectionComponent)->selectedIds)->toBe([]);
    });

    it('clears selection', function () {
        $component = new TestSelectionComponent;
        $component->selectedIds = ['1', '2', '3'];

        $component->clearSelection();

        expect($component->selectedIds)->toBe([]);
    });

    it('selects all given ids', function () {
        $component = new TestSelectionComponent;

        $component->selectAll(['a', 'b', 'c']);

        expect($component->selectedIds)->toBe(['a', 'b', 'c']);
    });

    it('returns selected count', function () {
        $component = new TestSelectionComponent;
        $component->selectedIds = ['x', 'y'];

        expect($component->selected_count)->toBe(2);
    });

    it('count is zero when nothing selected', function () {
        expect((new TestSelectionComponent)->selected_count)->toBe(0);
    });
});

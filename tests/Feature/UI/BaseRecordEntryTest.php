<?php

declare(strict_types=1);

namespace Tests\Feature\UI;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\UI\Livewire\BaseRecordEntry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

class ConcreteTestRecordEntry extends BaseRecordEntry
{
    public string $title = '';

    public function edit(string $id): void
    {
        $this->editingId = $id;
        $this->title = 'Existing Record '.$id;
        $this->showModal = true;
    }

    protected function resetForm(): void
    {
        parent::resetForm();
        $this->title = '';
    }

    public function triggerRejected(): void
    {
        $this->handleError(function (): void {
            throw new RejectedException('Validation rejected');
        });
    }

    public function triggerException(): void
    {
        $this->handleError(function (): void {
            throw new \RuntimeException('General fatal failure');
        });
    }

    public function render(): string
    {
        return '<div>Concrete Record Entry</div>';
    }
}

describe('8XMYS: BaseRecordEntry Feature Tests', function (): void {
    test('8XMYS-FR-UI-038-A: initial state has closed modal and null editingId', function (): void {
        $test = Livewire::test(ConcreteTestRecordEntry::class);

        expect($test->get('showModal'))->toBeFalse()
            ->and($test->get('editingId'))->toBeNull();
    });

    test('8XMYS-FR-UI-038-B: create opens modal and resets editingId', function (): void {
        $test = Livewire::test(ConcreteTestRecordEntry::class);

        $test->call('create');

        expect($test->get('showModal'))->toBeTrue()
            ->and($test->get('editingId'))->toBeNull();
    });

    test('8XMYS-FR-UI-038-C: edit sets editingId, opens modal, and populates form data', function (): void {
        $test = Livewire::test(ConcreteTestRecordEntry::class);

        $test->call('edit', 'rec-123');

        expect($test->get('showModal'))->toBeTrue()
            ->and($test->get('editingId'))->toBe('rec-123')
            ->and($test->get('title'))->toBe('Existing Record rec-123');
    });

    test('8XMYS-FR-UI-038-D: cancel closes modal and resets form state', function (): void {
        $test = Livewire::test(ConcreteTestRecordEntry::class);

        $test->call('edit', 'rec-456');
        expect($test->get('showModal'))->toBeTrue();

        $test->call('cancel');

        expect($test->get('showModal'))->toBeFalse()
            ->and($test->get('editingId'))->toBeNull()
            ->and($test->get('title'))->toBe('');
    });

    test('8XMYS-FR-UI-038-E: handleError gracefully traps RejectedException and generic Throwable', function (): void {
        $test = Livewire::test(ConcreteTestRecordEntry::class);

        $test->call('triggerRejected');
        $test->call('triggerException');

        // Does not crash
        expect($test->get('showModal'))->toBeFalse();
    });
});

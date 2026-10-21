<?php

declare(strict_types=1);

namespace Tests\Feature\UI;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\UI\Livewire\BaseFormView;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

class ConcreteTestFormView extends BaseFormView
{
    public string $name = '';

    public bool $savedSuccessfully = false;

    public function changeName(string $name): void
    {
        $this->name = $name;
        $this->markDirty();
    }

    public function submitSuccess(): void
    {
        $this->handleSave(function (): void {
            $this->savedSuccessfully = true;
        });
    }

    public function submitRejected(string $msg): void
    {
        $this->handleSave(function () use ($msg): void {
            throw new RejectedException($msg);
        });
    }

    public function submitFailure(): void
    {
        $this->handleSave(function (): void {
            throw new \RuntimeException('General system failure');
        });
    }

    public function render(): string
    {
        return '<div>Concrete Form View</div>';
    }
}

describe('8XMYS: BaseFormView Feature Tests', function (): void {
    test('8XMYS-FR-UI-037-A: initial dirty state is false and showConfirm is false', function (): void {
        $test = Livewire::test(ConcreteTestFormView::class);

        expect($test->get('isDirty'))->toBeFalse()
            ->and($test->get('showConfirm'))->toBeFalse();
    });

    test('8XMYS-FR-UI-037-B: markDirty tracks unsaved modifications', function (): void {
        $test = Livewire::test(ConcreteTestFormView::class);

        $test->call('changeName', 'Updated Name');

        expect($test->get('isDirty'))->toBeTrue()
            ->and($test->get('name'))->toBe('Updated Name');
    });

    test('8XMYS-FR-UI-037-C: handleSave clears isDirty flag on successful save', function (): void {
        $test = Livewire::test(ConcreteTestFormView::class);
        $test->call('changeName', 'Valid Save');
        expect($test->get('isDirty'))->toBeTrue();

        $test->call('submitSuccess');

        expect($test->get('isDirty'))->toBeFalse()
            ->and($test->instance()->savedSuccessfully)->toBeTrue();
    });

    test('8XMYS-FR-UI-037-D: handleSave catches RejectedException without crashing', function (): void {
        $test = Livewire::test(ConcreteTestFormView::class);
        $test->call('changeName', 'Rejected Value');

        $test->call('submitRejected', 'Custom business invariant rejected');

        // Form remains dirty and component did not throw uncaught exception
        expect($test->get('isDirty'))->toBeTrue();
    });

    test('8XMYS-FR-UI-037-E: handleSave catches general Throwable without crashing', function (): void {
        $test = Livewire::test(ConcreteTestFormView::class);
        $test->call('changeName', 'Crash Value');

        $test->call('submitFailure');

        expect($test->get('isDirty'))->toBeTrue();
    });
});

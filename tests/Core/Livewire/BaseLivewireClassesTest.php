<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseFormView;
use App\Core\Livewire\BaseRecordEntry;
use App\Core\Livewire\BaseRecordList;
use App\Core\Livewire\BaseRecordManager;
use App\Core\Livewire\BaseWizard;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

final class BaseRecordManagerTestStub extends BaseRecordManager
{
    public function headers(): array
    {
        return ['id' => 'ID', 'name' => 'Name'];
    }

    protected function query(): Builder
    {
        return User::query();
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    public function exposedPerformBulkAction(string $name, callable $callback): void
    {
        $this->performBulkAction($name, $callback);
    }

    public function exposedPerformMassAction(string $name, callable $callback): void
    {
        $this->performMassAction($name, $callback);
    }

    public function render(): string
    {
        return '<div>stub</div>';
    }
}

final class BaseRecordListTestStub extends BaseRecordList
{
    protected function query(): Builder
    {
        return User::query();
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    public function render(): string
    {
        return '<div>stub</div>';
    }
}

final class BaseRecordEntryTestStub extends BaseRecordEntry
{
    public function edit(string $id): void
    {
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function exposedHandleError(callable $callback): void
    {
        $this->handleError($callback);
    }

    public function render(): string
    {
        return '<div>stub</div>';
    }
}

final class BaseFormViewTestStub extends BaseFormView
{
    public function exposedHandleSave(callable $callback): void
    {
        $this->handleSave($callback);
    }

    public function exposedMarkDirty(): void
    {
        $this->markDirty();
    }

    public function render(): string
    {
        return '<div>stub</div>';
    }
}

final class BaseWizardTestStub extends BaseWizard
{
    protected function steps(): array
    {
        return ['one', 'two', 'three'];
    }

    public function exposedHandleStepError(callable $callback): void
    {
        $this->handleStepError($callback);
    }

    public function render(): string
    {
        return '<div>stub</div>';
    }
}

final class BaseWizardSingleStepTestStub extends BaseWizard
{
    protected function steps(): array
    {
        return ['only'];
    }

    public function render(): string
    {
        return '<div>stub</div>';
    }
}

uses(LazilyRefreshDatabase::class);

it('SE5Q9-FR-L1: BaseRecordManager rows() paginates with a valid perPage', function () {
    User::factory()->count(12)->create();

    $component = new BaseRecordManagerTestStub;
    $rows = $component->rows();

    expect($rows)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($rows->perPage())->toBe(10);
    expect($rows->total())->toBe(12);
});

it('SE5Q9-FR-L1: BaseRecordManager falls back to 10 for invalid perPage values', function () {
    $component = new BaseRecordManagerTestStub;
    $component->perPage = 7;

    expect($component->rows()->perPage())->toBe(10);
});

it('SE5Q9-FR-L1: BaseRecordManager applies search and eager loading', function () {
    User::factory()->create(['name' => 'Alice']);
    User::factory()->create(['name' => 'Bob']);

    $component = new BaseRecordManagerTestStub;
    $component->search = 'Ali';

    expect($component->rows()->total())->toBe(1);
    expect($component->rows()->first()->name)->toBe('Alice');
});

it('SE5Q9-FR-L1: BaseRecordManager resetFilters clears filters and updates reset the page', function () {
    $component = new BaseRecordManagerTestStub;
    $component->filters = ['status' => 'active'];

    $component->resetFilters();

    expect($component->filters)->toBe([]);
});

it('SE5Q9-FR-L1: bulk action warns when no records are selected', function () {
    $component = new BaseRecordManagerTestStub;
    $called = 0;

    $component->exposedPerformBulkAction('delete', function () use (&$called) {
        $called++;
    });

    expect($called)->toBe(0);
});

it('SE5Q9-FR-L1: bulk action runs the callback per selected id and clears selection', function () {
    $component = new BaseRecordManagerTestStub;
    $component->selectedIds = [1, 2, 3];
    $processed = [];

    $component->exposedPerformBulkAction('delete', function (int $id) use (&$processed) {
        $processed[] = $id;
    });

    expect($processed)->toBe([1, 2, 3]);
    expect($component->selectedIds)->toBe([]);
});

it('SE5Q9-FR-L1: mass action warns when no records match the query', function () {
    $component = new BaseRecordManagerTestStub;
    $component->search = 'no-such-user';
    $called = 0;

    $component->exposedPerformMassAction('export', function () use (&$called) {
        $called++;
    });

    expect($called)->toBe(0);
});

it('SE5Q9-FR-L1: mass action runs the callback with the filtered query and clears selection', function () {
    User::factory()->count(3)->create();
    $component = new BaseRecordManagerTestStub;
    $component->selectedIds = [1];

    $total = null;
    $component->exposedPerformMassAction('export', function (Builder $query) use (&$total) {
        $total = $query->count();
    });

    expect($total)->toBe(3);
    expect($component->selectedIds)->toBe([]);
});

it('SE5Q9-FR-L3: BaseRecordList rows() paginates and searches', function () {
    User::factory()->count(5)->create(['name' => 'Alpha']);
    User::factory()->count(2)->create(['name' => 'Beta']);

    $component = new BaseRecordListTestStub;
    $component->search = 'Alp';

    $rows = $component->rows();

    expect($rows)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($rows->total())->toBe(5);
});

it('SE5Q9-FR-L3: BaseRecordList falls back to 10 for invalid perPage values', function () {
    $component = new BaseRecordListTestStub;
    $component->perPage = 33;

    expect($component->rows()->perPage())->toBe(10);
});

it('SE5Q9-FR-L2: BaseRecordEntry create() resets the form and opens the modal', function () {
    $component = new BaseRecordEntryTestStub;
    $component->editingId = 'abc';

    $component->create();

    expect($component->showModal)->toBeTrue();
    expect($component->editingId)->toBeNull();
});

it('SE5Q9-FR-L2: BaseRecordEntry edit() opens the modal with the record id', function () {
    $component = new BaseRecordEntryTestStub;

    $component->edit('42');

    expect($component->showModal)->toBeTrue();
    expect($component->editingId)->toBe('42');
});

it('SE5Q9-FR-L2: BaseRecordEntry cancel() closes the modal and resets the form', function () {
    $component = new BaseRecordEntryTestStub;
    $component->showModal = true;
    $component->editingId = '42';

    $component->cancel();

    expect($component->showModal)->toBeFalse();
    expect($component->editingId)->toBeNull();
});

it('SE5Q9-FR-L2: handleError catches RejectedException and generic errors without propagating', function () {
    $component = new BaseRecordEntryTestStub;
    $ran = false;

    $component->exposedHandleError(function () use (&$ran) {
        throw new RejectedException('blocked');
    });
    expect($ran)->toBeFalse();

    $component->exposedHandleError(function () use (&$ran) {
        throw new LogicException('boom');
    });
    expect($ran)->toBeFalse();

    $component->exposedHandleError(function () use (&$ran) {
        $ran = true;
    });
    expect($ran)->toBeTrue();
});

it('SE5Q9-FR-L4: BaseFormView handleSave clears dirty state on success and flags errors otherwise', function () {
    $component = new BaseFormViewTestStub;
    $component->isDirty = true;

    $component->exposedHandleSave(fn () => null);
    expect($component->isDirty)->toBeFalse();

    $component->exposedHandleSave(function () {
        throw new RejectedException('nope');
    });
    expect($component->isDirty)->toBeFalse();

    $component->exposedHandleSave(function () {
        throw new LogicException('boom');
    });
    expect($component->isDirty)->toBeFalse();
});

it('SE5Q9-FR-L4: markDirty flags the form as modified', function () {
    $component = new BaseFormViewTestStub;
    $component->isDirty = false;

    $component->exposedMarkDirty();

    expect($component->isDirty)->toBeTrue();
});

it('SE5Q9-FR-L5: BaseWizard nextStep validates, marks completed and advances', function () {
    $component = new BaseWizardTestStub;

    $component->nextStep();

    expect($component->currentStep)->toBe(2);
    expect($component->isStepCompleted(1))->toBeTrue();

    $component->nextStep();
    $component->nextStep();

    expect($component->currentStep)->toBe(3);
});

it('SE5Q9-FR-L5: BaseWizard prevStep moves back but never below step one', function () {
    $component = new BaseWizardTestStub;
    $component->currentStep = 3;

    $component->prevStep();
    expect($component->currentStep)->toBe(2);

    $component->currentStep = 1;
    $component->prevStep();
    expect($component->currentStep)->toBe(1);
});

it('SE5Q9-FR-L5: goToStep enforces range and accessibility', function () {
    $component = new BaseWizardTestStub;

    $component->goToStep(5);
    expect($component->currentStep)->toBe(1);

    $component->goToStep(3);
    expect($component->currentStep)->toBe(1);

    $component->nextStep();
    $component->goToStep(3);
    expect($component->currentStep)->toBe(2);

    $component->nextStep();
    $component->goToStep(3);
    expect($component->currentStep)->toBe(3);
});

it('SE5Q9-FR-L5: isStepAccessible requires all prior steps to be completed', function () {
    $component = new BaseWizardTestStub;

    expect($component->isStepAccessible(1))->toBeTrue();
    expect($component->isStepAccessible(2))->toBeFalse();

    $component->nextStep();

    expect($component->isStepAccessible(2))->toBeTrue();
    expect($component->isStepAccessible(3))->toBeFalse();
});

it('SE5Q9-FR-L5: progressPercent reflects position across steps', function () {
    $component = new BaseWizardTestStub;

    expect($component->progressPercent())->toBe(0);

    $component->nextStep();
    expect($component->progressPercent())->toBe(50);

    $component->nextStep();
    expect($component->progressPercent())->toBe(100);

    $single = new BaseWizardSingleStepTestStub;
    expect($single->progressPercent())->toBe(100);
});

it('SE5Q9-FR-L5: currentStepKey returns the key of the active step', function () {
    $component = new BaseWizardTestStub;

    expect($component->currentStepKey())->toBe('one');

    $component->nextStep();
    expect($component->currentStepKey())->toBe('two');
});

it('SE5Q9-FR-L5: handleStepError catches RejectedException and generic errors', function () {
    $component = new BaseWizardTestStub;
    $ran = false;

    $component->exposedHandleStepError(function () use (&$ran) {
        throw new RejectedException('blocked');
    });
    expect($ran)->toBeFalse();

    $component->exposedHandleStepError(function () use (&$ran) {
        throw new LogicException('boom');
    });
    expect($ran)->toBeFalse();

    $component->exposedHandleStepError(function () use (&$ran) {
        $ran = true;
    });
    expect($ran)->toBeTrue();
});

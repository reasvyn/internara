<?php

declare(strict_types=1);

namespace Tests\Feature\UI;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\UI\Livewire\BaseWizard;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

class ConcreteTestWizard extends BaseWizard
{
    public bool $validateCalled = false;

    protected function steps(): array
    {
        return [
            'account_info',
            'profile_details',
            'confirmation',
        ];
    }

    protected function validateCurrentStep(): void
    {
        $this->validateCalled = true;
    }

    public function triggerError(bool $isRejected): void
    {
        $this->handleStepError(function () use ($isRejected): void {
            if ($isRejected) {
                throw new RejectedException('Step validation rejected');
            }
            throw new \RuntimeException('Unexpected step error');
        });
    }

    public function render(): string
    {
        return '<div>Concrete Wizard</div>';
    }
}

describe('8XMYS: BaseWizard Feature Tests', function (): void {
    test('8XMYS-FR-UI-039-A: wizard initializes at step 1 with 0 percent progress', function (): void {
        $test = Livewire::test(ConcreteTestWizard::class);

        expect($test->get('currentStep'))->toBe(1)
            ->and($test->get('completedSteps'))->toBeEmpty()
            ->and($test->instance()->currentStepKey())->toBe('account_info')
            ->and($test->instance()->isCurrentStep(1))->toBeTrue()
            ->and($test->instance()->progressPercent())->toBe(0);
    });

    test('8XMYS-FR-UI-039-B: nextStep validates step, records completion, and advances step', function (): void {
        $test = Livewire::test(ConcreteTestWizard::class);

        $test->call('nextStep');

        expect($test->get('currentStep'))->toBe(2)
            ->and($test->instance()->isStepCompleted(1))->toBeTrue()
            ->and($test->instance()->currentStepKey())->toBe('profile_details')
            ->and($test->instance()->progressPercent())->toBe(50)
            ->and($test->instance()->validateCalled)->toBeTrue();
    });

    test('8XMYS-FR-UI-039-C: nextStep does not advance beyond maximum step count', function (): void {
        $test = Livewire::test(ConcreteTestWizard::class);

        $test->call('nextStep'); // to step 2
        $test->call('nextStep'); // to step 3
        expect($test->get('currentStep'))->toBe(3)
            ->and($test->instance()->progressPercent())->toBe(100);

        $test->call('nextStep'); // attempts step 4 -> remains 3
        expect($test->get('currentStep'))->toBe(3);
    });

    test('8XMYS-FR-UI-039-D: prevStep decrements currentStep but never drops below 1', function (): void {
        $test = Livewire::test(ConcreteTestWizard::class);

        $test->call('prevStep');
        expect($test->get('currentStep'))->toBe(1);

        $test->call('nextStep'); // to step 2
        expect($test->get('currentStep'))->toBe(2);

        $test->call('prevStep'); // back to 1
        expect($test->get('currentStep'))->toBe(1);
    });

    test('8XMYS-FR-UI-039-E: goToStep prevents jumping ahead to uncompleted steps', function (): void {
        $test = Livewire::test(ConcreteTestWizard::class);

        // Cannot jump directly to step 3 when step 1 and 2 are not completed
        $test->call('goToStep', 3);
        expect($test->get('currentStep'))->toBe(1);

        // Complete step 1
        $test->call('nextStep');
        expect($test->get('currentStep'))->toBe(2);

        // Can go back to step 1
        $test->call('goToStep', 1);
        expect($test->get('currentStep'))->toBe(1);
    });

    test('8XMYS-FR-UI-039-F: handleStepError traps errors safely without component crash', function (): void {
        $test = Livewire::test(ConcreteTestWizard::class);

        $test->call('triggerError', true);
        $test->call('triggerError', false);

        expect($test->get('currentStep'))->toBe(1);
    });
});

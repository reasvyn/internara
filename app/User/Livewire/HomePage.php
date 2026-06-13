<?php

declare(strict_types=1);

namespace App\User\Livewire;

use App\Enrollment\Registration\Actions\ReadRegistrationAvailabilityAction;
use App\Setup\Entities\SetupEntity;
use Illuminate\View\View;
use Livewire\Component;

final class HomePage extends Component
{
    public array $registration = [];

    public function mount(ReadRegistrationAvailabilityAction $action): void
    {
        if (! SetupEntity::get()->isInstalled()) {
            $this->redirectRoute('setup');

            return;
        }

        if (auth()->check()) {
            $this->redirectRoute('dashboard');

            return;
        }

        $this->registration = $action->execute();
    }

    public function render(): View
    {
        return view('livewire.user.home-page')
            ->layout('core::layouts.guest', ['title' => __('user.home.page_title')]);
    }
}

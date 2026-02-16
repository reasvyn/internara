<?php

declare(strict_types=1);

namespace Modules\Auth\Registration\Livewire;

use Livewire\Component;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\User\Livewire\UserForm;

class RegisterSuperAdmin extends Component
{
    public UserForm $form;

    protected AuthService $authService;

    /**
     * Initializes the component.
     */
    public function boot(
        AuthService $authService,
        \Modules\User\Services\Contracts\SuperAdminService $superAdminService,
    ): void {
        $this->authService = $authService;
        $this->form->id = $superAdminService->getSuperAdmin(['id'])?->id;
    }

    /**
     * Mounts the component.
     */
    public function mount()
    {
        $this->form->name = 'Administrator';
    }

    /**
     * Handles the registration of the SuperAdmin account.
     */
    public function register()
    {
        $this->form->validate();

        $registeredUser = $this->authService->register($this->form->except('id'), 'super-admin');

        if ($registeredUser) {
            $this->dispatch('super-admin-registered', userId: $registeredUser->getKey());
        }
    }

    /**
     * Renders the component view.
     */
    public function render()
    {
        return view('auth::livewire.register-super-admin')->layout(
            'auth::components.layouts.auth',
            [
                'title' => __('Buat Akun Utama | :site_title', [
                    'site_title' => setting('site_title', 'Internara'),
                ]),
            ],
        );
    }
}

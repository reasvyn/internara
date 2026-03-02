<?php

declare(strict_types=1);

namespace Modules\Auth\Registration\Livewire;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\User\Livewire\Forms\UserForm;

class RegisterSuperAdmin extends Component
{
    public UserForm $form;

    protected AuthService $authService;

    /**
     * Initializes the component.
     */
    public function boot(
        AuthService $authService,
        SuperAdminService $superAdminService,
    ): void {
        $this->authService = $authService;
        $this->form->id = $superAdminService->getSuperAdmin()?->id;
    }

    /**
     * Mounts the component.
     */
    public function mount()
    {
        $this->form->name = 'Administrator';
        $this->form->roles = [\Modules\Permission\Enums\Role::SUPER_ADMIN->value];
        $this->form->status = 'active';
    }

    /**
     * Handles the registration of the SuperAdmin account.
     */
    public function register()
    {
        try {
            $superAdminService = app(SuperAdminService::class);

            // During setup phase, allow re-linking to an existing user record with the same email
            // to prevent "Email already taken" errors when repeating this step.
            if (! setting('app_installed', false)) {
                $existing = app(\Modules\User\Services\Contracts\UserService::class)->findByEmail(
                    $this->form->email,
                );

                if ($existing) {
                    $this->form->id = $existing->id;
                }
            }

            if (empty($this->form->username) && ! empty($this->form->email)) {
                $this->form->username = strstr($this->form->email, '@', true) ?: $this->form->email;
            }

            $this->form->validate();

            if ($this->form->id) {
                $registeredUser = $superAdminService->update(
                    $this->form->id,
                    $this->form->all(),
                );
            } else {
                $registeredUser = $superAdminService->create($this->form->all());
            }

            if ($registeredUser) {
                flash()->success('shared::messages.record_saved');
                $this->dispatch('super_admin_registered', userId: $registeredUser->getKey());
            }
            } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
            } catch (\Modules\Exception\AppException $e) {
            flash()->error($e->getUserMessage());
            } catch (\Exception $e) {
            flash()->error('auth::exceptions.registration_failed');
            \Illuminate\Support\Facades\Log::error('SuperAdmin Registration Failed.', [
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                'error_type' => get_class($e),
            ]);
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
                'title' => __('auth::ui.register_super_admin.page_title', [
                    'site_title' => setting('site_title', 'Internara'),
                ]),
            ],
        );
    }
}

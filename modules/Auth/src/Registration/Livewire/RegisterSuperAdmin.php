<?php

declare(strict_types=1);

namespace Modules\Auth\Registration\Livewire;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
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
        $this->form->roles = [\Modules\Permission\Enums\Role::SUPER_ADMIN->value];
        $this->form->status = 'active';
    }

    /**
     * Handles the registration of the SuperAdmin account.
     */
    public function register()
    {
        try {
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

            $this->form->validate();

            if ($this->form->id) {
                // Bypass gate during setup phase as roles might not be fully seeded yet
                if (! setting('app_installed', false)) {
                    Gate::shouldReceive('authorize')->andReturn(true);
                }

                $registeredUser = app(\Modules\User\Services\Contracts\UserService::class)->update(
                    $this->form->id,
                    $this->form->all(),
                );
            } else {
                $registeredUser = $this->authService->register(
                    $this->form->all(),
                    \Modules\Permission\Enums\Role::SUPER_ADMIN->value,
                );
            }

            if ($registeredUser) {
                flash()->success(__('shared::messages.record_saved'));
                $this->dispatch('super_admin_registered', userId: $registeredUser->getKey());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Modules\Exception\AppException $e) {
            flash()->error($e->getUserMessage());
        } catch (\Exception $e) {
            flash()->error(__('shared::exceptions.creation_failed', ['record' => 'Administrator']));
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

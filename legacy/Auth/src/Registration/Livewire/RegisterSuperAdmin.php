<?php

declare(strict_types=1);

namespace Modules\Auth\Registration\Livewire;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\Exception\AppException;
use Modules\Permission\Enums\Role;
use Modules\Shared\Services\UsernameGenerator;
use Modules\User\Livewire\Forms\UserForm;
use Modules\User\Services\Contracts\UserService;

class RegisterSuperAdmin extends Component
{
    public UserForm $form;

    protected AuthService $authService;

    /**
     * Initializes the component.
     */
    public function boot(AuthService $authService, SuperAdminService $superAdminService): void
    {
        $this->authService = $authService;
        $this->form->id = $superAdminService->getSuperAdmin()?->id;
    }

    /**
     * Mounts the component.
     */
    public function mount()
    {
        $this->form->name = 'Administrator';
        $this->form->roles = [Role::SUPER_ADMIN->value];
        $this->form->status = 'active';
    }

    /**
     * Handles the registration of the SuperAdmin account.
     */
    public function register()
    {
        try {
            $superAdminService = app(SuperAdminService::class);
            $usernameGenerator = app(UsernameGenerator::class);

            // Enforce permanent system identity
            $this->form->name = 'Administrator';

            // During setup phase, allow re-linking to an existing user record with the same email
            // to prevent "Email already taken" errors when repeating this step.
            if (! setting('app_installed', false)) {
                $existing = app(UserService::class)->findByEmail($this->form->email);

                if ($existing) {
                    $this->form->id = $existing->id;
                }
            }

            // Generate a permanent, role-based username if not already set
            if (empty($this->form->username) && ! empty($this->form->email)) {
                $this->form->username = $usernameGenerator->generate(
                    $this->form->email,
                    Role::SUPER_ADMIN->value,
                );
            }

            $this->form->validate();

            // Always use create() which handles idempotency (updateOrCreate)
            // and setup-specific constraints internally.
            $registeredUser = $superAdminService->create($this->form->all());

            if ($registeredUser) {
                // [Audit] Log explicit SuperAdmin creation during setup
                activity('setup')
                    ->performedOn($registeredUser)
                    ->causedBy($registeredUser)
                    ->withProperties(['step' => 'account_creation', 'role' => 'super_admin'])
                    ->log('Initial SuperAdmin account created and verified.');

                flash()->success('shared::messages.record_saved');
                $this->dispatch('super_admin_registered', userId: $registeredUser->getKey());
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (AppException $e) {
            flash()->error($e->getUserMessage());
        } catch (\Exception $e) {
            flash()->error('auth::exceptions.registration_failed');
            Log::error('SuperAdmin Registration Failed.', [
                'correlation_id' => Str::uuid()->toString(),
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

<?php

declare(strict_types=1);

namespace Modules\User\Livewire;

use Livewire\Component;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\User\Livewire\Forms\UserForm;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

/**
 * Class Form
 *
 * Thin orchestrator for User creation and updates.
 */
class Form extends Component
{
    use HandlesAppException;

    /**
     * The form object.
     */
    public UserForm $form;

    /**
     * The user service instance.
     */
    protected UserService $userService;

    /**
     * Boot the component.
     */
    public function boot(UserService $userService): void
    {
        $this->userService = $userService;
    }

    /**
     * Mount the component.
     */
    public function mount(?string $id = null): void
    {
        if ($id) {
            $user = User::findOrFail($id);
            $this->form . setUser($user);
        }
    }

    /**
     * Save the user.
     */
    public function save()
    {
        $this->validate();

        try {
            if ($this->form->id) {
                $this->userService->update($this->form->id, $this->form->all());
                $message = __('User updated successfully.');
            } else {
                $this->userService->create($this->form->all());
                $message = __('User created successfully.');
            }

            $this->dispatch('success', message: $message);

            return redirect()->route('users.index');
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('user::livewire.users.form');
    }
}

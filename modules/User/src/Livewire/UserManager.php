<?php

declare(strict_types=1);

namespace Modules\User\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\User\Livewire\Forms\UserForm;
use Modules\User\Services\Contracts\UserService;

class UserManager extends Component
{
    use HandlesAppException;

    public UserForm $form;

    public bool $formModal = false;

    protected UserService $userService;

    public function boot(UserService $userService): void
    {
        $this->userService = $userService;
    }

    public function add(): void
    {
        $this->form->reset();
        $this->formModal = true;
    }

    public function edit(string $id): void
    {
        $user = $this->userService->find($id);
        $this->form->setUser($user);
        $this->formModal = true;
    }

    public function save(): void
    {
        $this->form->validate();

        try {
            if ($this->form->id) {
                $this->userService->update($this->form->id, $this->form->all());
            } else {
                $this->userService->create($this->form->all());
            }

            $this->formModal = false;
            $this->dispatch('success', message: __('User saved successfully.'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    public function render(): View
    {
        return view('user::livewire.user-manager');
    }
}

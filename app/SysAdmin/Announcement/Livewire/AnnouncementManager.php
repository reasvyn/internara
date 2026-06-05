<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Livewire;

use App\SysAdmin\Announcement\Actions\SendAnnouncementAction;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Livewire\Forms\AnnouncementForm;
use App\SysAdmin\Announcement\Models\Announcement;
use App\User\Enums\Role;
use App\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class AnnouncementManager extends Component
{
    public AnnouncementForm $form;

    public bool $showForm = false;

    public bool $showConfirm = false;

    public ?string $confirmId = null;

    public string $confirmActionType = '';

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function save(SendAnnouncementAction $action): void
    {
        if ($this->form->sendToAll) {
            $this->form->target_roles = [];
        }

        $this->form->validate();

        $action->execute($this->form->toPayload());

        flash()->success(__('announcement.sent'));

        $this->resetForm();
    }

    public function confirmDelete(string $id): void
    {
        $this->confirmId = $id;
        $this->confirmActionType = 'delete';
        $this->showConfirm = true;
    }

    public function confirmPublish(string $id): void
    {
        $this->confirmId = $id;
        $this->confirmActionType = 'publish';
        $this->showConfirm = true;
    }

    public function confirmAction(): void
    {
        $id = $this->confirmId;

        if ($id === null) {
            return;
        }

        $action = app(SendAnnouncementAction::class);

        if ($this->confirmActionType === 'delete') {
            $announcement = Announcement::where('created_by', Auth::id())->findOrFail($id);
            $announcement->delete();
            flash()->success(__('announcement.deleted'));
        } elseif ($this->confirmActionType === 'publish') {
            $announcement = Announcement::where('created_by', Auth::id())->findOrFail($id);

            if (! $announcement->status->canTransitionTo(AnnouncementStatus::PUBLISHED)) {
                flash()->error(__('announcement.cannot_publish'));

                return;
            }

            $action->publish($announcement);
            flash()->success(__('announcement.published'));
        }

        $this->showConfirm = false;
        $this->confirmId = null;
        $this->confirmActionType = '';
    }

    public function resetForm(): void
    {
        $this->form->reset();
        $this->showForm = false;
    }

    public function render(): View
    {
        return view('sysadmin.announcement-manager', [
            'announcements' => Announcement::latest()->take(50)->get(),
            'roles' => collect(Role::excludeSuperAdmin())->map(fn (Role $role) => [
                'id' => $role->value,
                'name' => $role->label(),
            ]),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Livewire;

use App\Auth\Permissions\Enums\Role;
use App\Core\Livewire\BaseRecordManager;
use App\SysAdmin\Announcement\Actions\DeleteAnnouncementAction;
use App\SysAdmin\Announcement\Actions\PublishAnnouncementAction;
use App\SysAdmin\Announcement\Actions\SendAnnouncementAction;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\SysAdmin\Announcement\Livewire\Forms\AnnouncementForm;
use App\SysAdmin\Announcement\Models\Announcement;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use TallStackUi\Traits\Interactions;

class AnnouncementManager extends BaseRecordManager
{
    use Interactions;

    public AnnouncementForm $form;

    public bool $showForm = false;

    public ?string $confirmId = null;

    public string $confirmActionType = '';

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'title', 'label' => __('announcement.fields.title'), 'sortable' => true],
            ['key' => 'type', 'label' => __('announcement.fields.type')],
            ['key' => 'status', 'label' => __('announcement.fields.status')],
            ['key' => 'created_at', 'label' => __('common.created_at'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Announcement::where('created_by', Auth::id());
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('title', 'like', "%{$this->search}%");
    }

    public function save(SendAnnouncementAction $action): void
    {
        if ($this->form->sendToAll) {
            $this->form->target_roles = [];
        }

        $this->form->validate();

        $action->execute($this->form->toPayload());

        $this->toast()->success(__('announcement.sent'))->send();

        $this->resetForm();
    }

    public function confirmDelete(string $id): void
    {
        $this->confirmId = $id;
        $this->confirmActionType = 'delete';
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmPublish(string $id): void
    {
        $this->confirmId = $id;
        $this->confirmActionType = 'publish';
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmAction(
        DeleteAnnouncementAction $delete,
        PublishAnnouncementAction $publish,
    ): void {
        $id = $this->confirmId;

        if ($id === null) {
            return;
        }

        if ($this->confirmActionType === 'delete') {
            $announcement = Announcement::where('created_by', Auth::id())->findOrFail($id);
            $delete->execute($announcement);
            $this->toast()->success(__('announcement.deleted'))->send();
        } elseif ($this->confirmActionType === 'publish') {
            $announcement = Announcement::where('created_by', Auth::id())->findOrFail($id);

            if (! $announcement->status->canTransitionTo(AnnouncementStatus::PUBLISHED)) {
                $this->toast()->error(__('announcement.cannot_publish'))->send();

                return;
            }

            $publish->execute($announcement);
            $this->toast()->success(__('announcement.published'))->send();
        }
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
        return view('sysadmin.announcement.announcement-manager', [
            'announcements' => $this->rows(),
            'roles' => collect(Role::excludeSuperAdmin())->map(
                fn (Role $role) => [
                    'id' => $role->value,
                    'name' => $role->label(),
                ],
            ),
        ]);
    }
}

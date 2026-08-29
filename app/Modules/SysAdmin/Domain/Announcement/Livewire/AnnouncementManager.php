<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Announcement\Livewire;

use App\Modules\Auth\Domain\Permissions\Enums\Role;
use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\SysAdmin\Domain\Announcement\Actions\DeleteAnnouncementAction;
use App\Modules\SysAdmin\Domain\Announcement\Actions\PublishAnnouncementAction;
use App\Modules\SysAdmin\Domain\Announcement\Actions\SendAnnouncementAction;
use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;
use App\Modules\SysAdmin\Domain\Announcement\Livewire\Forms\AnnouncementForm;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use App\Modules\User\Models\User;
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
            ['index' => 'title', 'label' => __('announcement.fields.title'), 'sortable' => true],
            ['index' => 'type', 'label' => __('announcement.fields.type')],
            ['index' => 'status', 'label' => __('announcement.fields.status')],
            ['index' => 'created_at', 'label' => __('common.created_at'), 'sortable' => true],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
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

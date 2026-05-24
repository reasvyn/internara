<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Actions\SendAnnouncementAction;
use App\Domain\Admin\Enums\AnnouncementStatus;
use App\Domain\Admin\Models\Announcement;
use App\Domain\Auth\Enums\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class AnnouncementManager extends Component
{
    public string $title = '';

    public string $message = '';

    public string $type = 'info';

    public string $status = 'draft';

    public ?string $scheduled_at = null;

    public ?string $link = null;

    /** @var string[] */
    public array $target_roles = [];

    public bool $sendToAll = true;

    public bool $showForm = false;

    public bool $showConfirm = false;

    public ?string $confirmId = null;

    public string $confirmActionType = '';

    public function boot(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin']), 403);
    }

    public function updatedSendToAll(bool $value): void
    {
        if ($value) {
            $this->target_roles = [];
        }
    }

    public function save(SendAnnouncementAction $action): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'type' => 'required|in:info,success,warning,error',
            'status' => 'required|in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date|after_or_equal:now|required_if:status,scheduled',
            'link' => 'nullable|string|max:500',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'string|exists:roles,name',
        ]);

        $action->execute([
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'status' => $this->status,
            'scheduled_at' => $this->status === 'scheduled' ? $this->scheduled_at : null,
            'link' => $this->link ?: null,
            'target_roles' => $this->sendToAll ? null : $this->target_roles,
        ]);

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
        $this->reset(['title', 'message', 'type', 'status', 'scheduled_at', 'link', 'target_roles', 'sendToAll', 'showForm']);
    }

    public function render(): View
    {
        return view('admin.announcement-manager', [
            'announcements' => Announcement::latest()->take(50)->get(),
            'roles' => collect(Role::excludeSuperAdmin())->map(fn (Role $role) => [
                'id' => $role->value,
                'name' => $role->label(),
            ]),
        ]);
    }
}

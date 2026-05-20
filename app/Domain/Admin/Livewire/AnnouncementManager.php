<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Actions\SendAnnouncementAction;
use App\Domain\Admin\Models\Announcement;
use App\Domain\Auth\Enums\Role;
use Illuminate\View\View;
use Livewire\Component;

class AnnouncementManager extends Component
{
    public string $title = '';

    public string $message = '';

    public string $type = 'info';

    public ?string $link = null;

    /** @var string[] */
    public array $target_roles = [];

    public bool $sendToAll = true;

    public bool $showForm = false;

    public function boot(): void
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);
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
            'link' => 'nullable|string|max:500',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'string|exists:roles,name',
        ]);

        $action->execute([
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'link' => $this->link ?: null,
            'target_roles' => $this->sendToAll ? null : $this->target_roles,
        ]);

        flash()->success(__('announcement.sent'));

        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['title', 'message', 'type', 'link', 'target_roles', 'sendToAll', 'showForm']);
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

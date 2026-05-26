<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire;

use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\User\Actions\DeleteNotificationAction;
use App\Domain\User\Actions\MarkAllAsReadAction;
use App\Domain\User\Actions\MarkAsReadAction;
use App\Domain\User\Actions\MarkBatchAsReadAction;
use App\Domain\User\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationCenter extends BaseRecordManager
{
    public bool $showViewer = false;

    public ?string $viewingNotificationId = null;

    public function viewNotification(string $id): void
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);

        if (! $notification->is_read) {
            app(MarkAsReadAction::class)->execute($notification);
            $this->dispatch('notification-read');
        }

        $this->viewingNotificationId = $id;
        $this->showViewer = true;
    }

    public function closeViewer(): void
    {
        $this->showViewer = false;
        $this->viewingNotificationId = null;
    }

    public function getViewedNotificationProperty(): ?Notification
    {
        if ($this->viewingNotificationId === null) {
            return null;
        }

        return Notification::where('user_id', Auth::id())
            ->where('id', $this->viewingNotificationId)
            ->first();
    }

    public function headers(): array
    {
        return [
            ['key' => 'title', 'label' => __('notifications.ui.message_col')],
            [
                'key' => 'created_at',
                'label' => __('notifications.ui.received_col'),
                'sortable' => true,
                'class' => 'max-sm:hidden',
            ],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        $user = Auth::user();

        return Notification::where('user_id', $user->id)
            ->when($user->hasRole(['super_admin', 'admin']), function (Builder $q) {});
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('message', 'like', "%{$this->search}%");
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query->when($this->filters['status'] ?? null, function ($q, $status) {
            if ($status === 'unread') {
                $q->where('is_read', false);
            } elseif ($status === 'read') {
                $q->where('is_read', true);
            }
        });
    }

    public function markAsRead(string $id, MarkAsReadAction $action): void
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $action->execute($notification);
        $this->dispatch('notification-read');
    }

    public function markAllAsRead(MarkAllAsReadAction $action): void
    {
        $action->execute(Auth::id());

        flash()->success(__('notifications.ui.success_mark_all'));
        $this->dispatch('notifications-read');
    }

    public function markSelectedAsRead(MarkBatchAsReadAction $action): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $action->execute(Auth::id(), $this->selectedIds);

        $this->dispatch('notifications-read');
        $this->clearSelection();

        flash()->success(__('notifications.ui.success_mark_selected'));
    }

    public function deleteSelected(DeleteNotificationAction $action): void
    {
        $this->performBulkAction(__('notifications.ui.delete_selected'), function ($id) use ($action) {
            $notification = Notification::where('user_id', Auth::id())->where('id', $id)->first();
            if ($notification) {
                $action->execute($notification);
            }
        });
    }

    public function render(): View
    {
        return view('user.notification-center');
    }
}

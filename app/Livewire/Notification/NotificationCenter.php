<?php

declare(strict_types=1);

namespace App\Livewire\Notification;

use App\Actions\Notification\DeleteNotificationAction;
use App\Actions\Notification\MarkAllAsReadAction;
use App\Actions\Notification\MarkAsReadAction;
use App\Livewire\Core\BaseRecordManager;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Universal Notification Center for all users.
 */
class NotificationCenter extends BaseRecordManager
{
    /**
     * Define columns.
     */
    public function headers(): array
    {
        return [
            ['key' => 'title', 'label' => __('notifications.ui.message_col')],
            [
                'key' => 'created_at',
                'label' => __('notifications.ui.received_col'),
                'sortable' => true,
            ],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Base query - scoped to current user.
     */
    protected function query(): Builder
    {
        return Notification::where('user_id', Auth::id());
    }

    /**
     * Search implementation.
     */
    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('title', 'like', "%{$this->search}%")->orWhere(
                'message',
                'like',
                "%{$this->search}%",
            );
        });
    }

    /**
     * Filter implementation.
     */
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

    public function markSelectedAsRead(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        Notification::whereIn('id', $this->selectedIds)
            ->where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

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

    public function getListeners(): array
    {
        $userId = Auth::id();

        return [
            "echo-private:App.Domain.User.Models.User.{$userId},.Illuminate\Notifications\Events\BroadcastNotificationCreated" => '$refresh',
        ];
    }

    public function render()
    {
        return view('livewire.notification.notification-center');
    }
}

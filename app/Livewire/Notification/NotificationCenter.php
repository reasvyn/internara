<?php

declare(strict_types=1);

namespace App\Livewire\Notification;

use App\Domain\Notification\Models\Notification;
use App\Livewire\Core\BaseRecordManager;
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

    public function markAsRead(string $id): void
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notification->markAsRead();
        $this->dispatch('notification-read');
    }

    public function markAllAsRead(): void
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $this->success(__('notifications.ui.success_mark_all'));
        $this->dispatch('notifications-read');
    }

    public function deleteSelected(): void
    {
        $this->performBulkAction(__('notifications.ui.delete_selected'), function ($id) {
            Notification::where('user_id', Auth::id())->where('id', $id)->delete();
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

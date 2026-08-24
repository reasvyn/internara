<?php

declare(strict_types=1);

namespace App\User\Notifications\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\User\Notifications\Actions\DeleteNotificationAction;
use App\User\Notifications\Actions\MarkAllAsReadAction;
use App\User\Notifications\Actions\MarkAsReadAction;
use App\User\Notifications\Actions\MarkBatchAsReadAction;
use App\User\Notifications\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use TallStackUi\Traits\Interactions;

class NotificationCenter extends BaseRecordManager
{
    use Interactions;

    public bool $showViewer = false;

    public ?string $viewingNotificationId = null;

    public function viewNotification(string $id, MarkAsReadAction $action): void
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);

        if (! $notification->is_read) {
            $action->execute($notification);
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
        return Notification::where('user_id', Auth::id());
    }

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

        $this->toast()->success(__('notifications.ui.success_mark_all'))->send();
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

        $this->toast()->success(__('notifications.ui.success_mark_selected'))->send();
    }

    public function askDeleteSelected(): void
    {
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmAction(DeleteNotificationAction $action): void
    {
        try {
            $this->performBulkAction(__('notifications.ui.delete_selected'), function ($id) use (
                $action,
            ) {
                $notification = Notification::where('user_id', Auth::id())->where('id', $id)->first();
                if ($notification) {
                    $action->execute($notification);
                }
            });
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
    }

    public function render(): View
    {
        return view('user.notifications.notification-center');
    }
}

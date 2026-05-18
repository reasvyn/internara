<div class="py-4">
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('notifications.ui.title') }}</h2>
        <p class="text-sm text-base-content/50 mt-1">{{ __('notifications.ui.subtitle') }}</p>
    </div>

    <x-mary-card class="bg-base-100 border border-base-content/10">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <x-mary-select
                wire:model.live="filters.status"
                :options="[
                    ['id' => 'unread', 'name' => __('notifications.ui.unread')],
                    ['id' => 'read', 'name' => __('notifications.ui.read')],
                ]"
                :placeholder="__('notifications.ui.all_status')"
                clearable
                class="sm:max-w-xs"
                aria-label="{{ __('notifications.ui.all_status') }}"
            />
            <x-mary-button :label="__('notifications.ui.mark_all_read')" icon="o-check-badge" class="btn-ghost btn-sm" wire:click="markAllAsRead" />
        </div>
    </x-mary-card>

    @if($this->selected_count() > 0)
        <div class="my-4 p-4 bg-base-200/50 border border-base-content/10 rounded-xl flex items-center justify-between gap-4" role="status" aria-live="polite">
            <p class="text-sm">
                <span class="font-semibold">{{ $this->selected_count() }}</span>
                {{ trans_choice('notifications.ui.selected_count', $this->selected_count()) }}
            </p>
            <div class="flex items-center gap-2">
                <x-mary-button
                    :label="__('notifications.ui.mark_read_batch')"
                    icon="o-check-badge"
                    class="btn-sm btn-ghost"
                    wire:click="markSelectedAsRead"
                />
                <x-mary-button
                    :label="__('notifications.ui.delete_selected')"
                    icon="o-trash"
                    class="btn-sm btn-error text-white"
                    :wire:confirm="__('notifications.ui.are_you_sure')"
                    wire:click="deleteSelected"
                />
            </div>
        </div>
    @endif

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model="selectedIds"
            class="table-sm max-sm:table-xs"
        >
            @scope('cell_title', $notification)
                @php $isRead = $notification->is_read; @endphp
                <div x-data="{ read: {{ $isRead ? 'true' : 'false' }} }">
                @if($notification->message)
                    <details
                        class="group py-2"
                        x-on:toggle="if($el.open && !read) { read = true; $wire.markAsRead('{{ $notification->id }}'); }"
                    >
                        <summary class="flex items-start gap-3 cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                            <div role="status" x-bind:class="read ? 'bg-base-200 text-base-content/40' : 'bg-primary/10 text-primary'" class="size-8 max-sm:hidden rounded-lg flex items-center justify-center shrink-0" aria-hidden="true">
                                <x-mary-icon x-show="!read" name="o-envelope" class="size-4" />
                                <x-mary-icon x-show="read" name="o-envelope-open" class="size-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span x-bind:class="read ? 'text-base-content/50' : 'text-base-content'" class="text-sm font-medium">
                                        {{ $notification->title }}
                                    </span>
                                    <span x-show="!read" class="size-1.5 rounded-full bg-error shrink-0" aria-label="{{ __('notifications.ui.unread') }}"></span>
                                </div>
                                <div x-bind:class="read ? 'text-base-content/40' : 'text-base-content/50'" class="text-xs line-clamp-1 break-words max-w-none truncate">
                                    {{ $notification->message }}
                                </div>
                            </div>
                            <div class="text-base-content/30 shrink-0 max-sm:hidden self-start mt-1 transition-transform group-open:rotate-180" aria-hidden="true">
                                <x-mary-icon name="o-chevron-down" class="size-4" />
                            </div>
                        </summary>
                        <div class="mt-2 text-xs max-w-none text-base-content/70 leading-relaxed">
                            {!! nl2br(e($notification->message)) !!}
                        </div>
                    </details>
                @else
                    <div
                        class="flex items-start gap-3 py-2 cursor-pointer"
                        role="button"
                        aria-label="{{ $notification->title }}"
                        x-on:click="if(!read) { read = true; $wire.markAsRead('{{ $notification->id }}'); }"
                    >
                        <div role="status" x-bind:class="read ? 'bg-base-200 text-base-content/40' : 'bg-primary/10 text-primary'" class="size-8 max-sm:hidden rounded-lg flex items-center justify-center shrink-0" aria-hidden="true">
                            <x-mary-icon x-show="!read" name="o-envelope" class="size-4" />
                            <x-mary-icon x-show="read" name="o-envelope-open" class="size-4" />
                        </div>
                        <div class="flex items-center gap-2 min-w-0">
                            <span x-bind:class="read ? 'text-base-content/50' : 'text-base-content'" class="text-sm font-medium">
                                {{ $notification->title }}
                            </span>
                            <span x-show="!read" class="size-1.5 rounded-full bg-error shrink-0" aria-label="{{ __('notifications.ui.unread') }}"></span>
                        </div>
                    </div>
                @endif
                </div>
            @endscope

            @scope('cell_created_at', $notification)
                <time datetime="{{ $notification->created_at->toIso8601String() }}" class="text-xs text-base-content/40 whitespace-nowrap max-sm:hidden">
                    {{ $notification->created_at->diffForHumans() }}
                </time>
            @endscope

            @scope('actions', $notification)
                <div class="flex justify-end gap-1">
                    @if($notification->link)
                        <x-mary-button icon="o-arrow-top-right-on-square" class="btn-ghost btn-sm" :link="$notification->link" x-on:click.prevent="$wire.markAsRead('{{ $notification->id }}'); window.open('{{ $notification->link }}', '_blank')" :aria-label="__('notifications.view_details')" />
                    @endif
                    @if(!$notification->is_read)
                        <x-mary-button icon="o-check" class="btn-ghost btn-sm text-success" x-on:click="$wire.markAsRead('{{ $notification->id }}')" :aria-label="__('notifications.ui.mark_all_read')" />
                    @endif
                </div>
            @endscope

        </x-mary-table>
    </div>
</div>

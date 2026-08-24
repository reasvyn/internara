<div class="py-4">
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('notifications.ui.title') }}</h2>
        <p class="text-base-content/60 mt-1 text-sm">{{ __('notifications.ui.subtitle') }}</p>
    </div>

    <x-mary-card class="bg-base-100 border-base-content/10 border">
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
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
            <div class="flex items-center gap-2">
                <x-ts-button
                    :text="__('notifications.ui.mark_all_read')"
                    icon="check-badge"
                    color="white"
                    sm
                    wire:click="markAllAsRead"
                />
                <x-ts-button
                    icon="arrow-path"
                    class="btn-square"
                    color="white"
                    sm
                    wire:click="$refresh"
                    :aria-label="__('notifications.ui.refresh')"
                />
            </div>
        </div>
    </x-mary-card>

    @if ($this->selected_count() > 0)
        <div
            class="bg-base-200/50 border-base-content/10 my-4 flex items-center justify-between gap-4 rounded-xl border p-4"
            role="status"
            aria-live="polite"
        >
            <p class="text-sm">
                <span class="font-semibold">{{ $this->selected_count() }}</span>
                {{ trans_choice('notifications.ui.selected_count', $this->selected_count()) }}
            </p>
            <div class="flex items-center gap-2">
                <x-ts-button
                    :text="__('notifications.ui.mark_read_batch')"
                    icon="check-badge"
                    color="white"
                    sm
                    wire:click="markSelectedAsRead"
                />
                <x-ts-button
                    :text="__('notifications.ui.delete_selected')"
                    icon="trash"
                    class="text-white"
                    color="red"
                    sm
                    wire:click="askDeleteSelected"
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
                    @if ($notification->message)
                        <details
                            class="group py-2"
                            x-on:toggle="if($el.open && ! read) { read = true; $wire.markAsRead('{{ $notification->id }}'); }"
                        >
                            <summary class="[&::-webkit-details-marker]:hidden flex cursor-pointer list-none items-start gap-3">
                                <div
                                    role="status"
                                    x-bind:class="
                                        read ? 'bg-base-200 text-base-content/40' : 'bg-primary/10 text-primary'
                                    "
                                    class="flex size-8 shrink-0 items-center justify-center rounded-lg max-sm:hidden"
                                    aria-hidden="true"
                                >
                                    <x-ts-icon x-show="! read" name="envelope" class="size-4" />
                                    <x-ts-icon x-show="read" name="envelope-open" class="size-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            x-bind:class="read ? 'text-base-content/50' : 'text-base-content'"
                                            class="text-sm font-medium"
                                        >
                                            {{ $notification->title }}
                                        </span>
                                        <span
                                            x-show="! read"
                                            class="bg-error size-1.5 shrink-0 rounded-full"
                                            aria-label="{{ __('notifications.ui.unread') }}"
                                        ></span>
                                    </div>
                                    <div
                                        x-bind:class="read ? 'text-base-content/60' : 'text-base-content/60'"
                                        class="line-clamp-1 max-w-none truncate text-xs break-words"
                                    >
                                        {{ $notification->message }}
                                    </div>
                                </div>
                                <div
                                    class="text-base-content/30 mt-1 shrink-0 self-start transition-transform group-open:rotate-180 max-sm:hidden"
                                    aria-hidden="true"
                                >
                                    <x-ts-icon name="chevron-down" class="size-4" />
                                </div>
                            </summary>
                            <div class="text-base-content/70 prose prose-sm mt-2 max-w-none text-xs leading-relaxed">
                                {!! Str::markdown($notification->message, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                            </div>
                        </details>
                    @else
                        <div
                            class="flex cursor-pointer items-start gap-3 py-2"
                            role="button"
                            tabindex="0"
                            aria-label="{{ $notification->title }}"
                            x-on:click="if(! read) { read = true; $wire.markAsRead('{{ $notification->id }}'); }"
                            x-on:keydown.enter.prevent="if(! read) { read = true; $wire.markAsRead('{{ $notification->id }}'); }"
                            x-on:keydown.space.prevent="if(! read) { read = true; $wire.markAsRead('{{ $notification->id }}'); }"
                        >
                            <div
                                role="status"
                                x-bind:class="read ? 'bg-base-200 text-base-content/40' : 'bg-primary/10 text-primary'"
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg max-sm:hidden"
                                aria-hidden="true"
                            >
                                <x-ts-icon x-show="! read" name="envelope" class="size-4" />
                                <x-ts-icon x-show="read" name="envelope-open" class="size-4" />
                            </div>
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    x-bind:class="read ? 'text-base-content/50' : 'text-base-content'"
                                    class="text-sm font-medium"
                                >
                                    {{ $notification->title }}
                                </span>
                                <span
                                    x-show="! read"
                                    class="bg-error size-1.5 shrink-0 rounded-full"
                                    aria-label="{{ __('notifications.ui.unread') }}"
                                ></span>
                            </div>
                        </div>
                    @endif
                </div>
            @endscope

            @scope('cell_created_at', $notification)
                <time
                    datetime="{{ $notification->created_at->toIso8601String() }}"
                    class="text-base-content/60 text-xs whitespace-nowrap max-sm:hidden"
                >
                    {{ $notification->created_at->diffForHumans() }}
                </time>
            @endscope

            @scope('actions', $notification)
                <div class="flex justify-end gap-1">
                    @if ($notification->link)
                        <x-ts-button
                            icon="arrow-top-right-on-square"
                            color="white"
                            sm
                            :link="$notification->link"
                            x-on:click.prevent="$wire.markAsRead('{{ $notification->id }}'); window.open('{{ $notification->link }}', '_blank')"
                            :aria-label="__('notifications.view_details')"
                        />
                    @endif
                    <x-ts-button
                        icon="eye"
                        color="white"
                        sm
                        x-on:click="$wire.viewNotification('{{ $notification->id }}')"
                        :aria-label="__('notifications.ui.read')"
                    />
                    @if (! $notification->is_read)
                        <x-ts-button
                            icon="check"
                            class="text-success"
                            color="white"
                            sm
                            x-on:click="$wire.markAsRead('{{ $notification->id }}')"
                            :aria-label="__('notifications.ui.mark_all_read')"
                        />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </div>

    {{-- Notification Viewer Modal --}}
    <x-ts-modal wire="showViewer" title="{{ $this->viewedNotification?->title ?? '' }}" blur size="lg">
        @if ($this->viewedNotification)
            <div class="space-y-4">
                <div class="prose prose-sm max-w-none">
                    {!! Str::markdown($this->viewedNotification->message ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                </div>
                <div class="text-base-content/40 border-base-content/10 border-t pt-3 text-xs">
                    {{ $this->viewedNotification->created_at->format('d M Y H:i') }}
                </div>
            </div>
        @endif

        <x-slot:footer>
            <x-ts-button :text="__('common.actions.close')" wire:click="closeViewer" color="white" sm />
            @if ($this->viewedNotification?->link)
                <x-ts-button
                    :text="__('notifications.view_details')"
                    icon="arrow-top-right-on-square"
                    :link="$this->viewedNotification->link"
                    class="btn-primary btn-sm"
                />
            @endif
        </x-slot:footer>
    </x-ts-modal>

    @include('user.notifications.components.notification-guide')
</div>

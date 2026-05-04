<div class="p-8">
    <x-layouts.manager 
        :title="__('notifications.ui.title')" 
        :subtitle="__('notifications.ui.subtitle')" 
        :rows="$this->rows()" 
        :headers="$this->headers()"
        :selected-count="$this->selected_count"
        :sort-by="$sortBy"
    >
        {{-- Top Actions --}}
        <x-slot:actions>
            <x-mary-button :label="__('notifications.ui.mark_all_read')" icon="o-check-badge" class="btn-ghost btn-sm" wire:click="markAllAsRead" />
        </x-slot:actions>

        {{-- Filters --}}
        <x-slot:filters>
            <x-mary-select 
                wire:model.live="filters.status" 
                :options="[
                    ['id' => 'unread', 'name' => __('notifications.ui.unread')],
                    ['id' => 'read', 'name' => __('notifications.ui.read')],
                ]" 
                :placeholder="__('notifications.ui.all_status')" 
                clearable 
                class="rounded-xl border-base-300"
            />
        </x-slot:filters>

        {{-- Bulk Actions --}}
        <x-slot:bulkActions>
            <x-mary-button 
                :label="__('notifications.ui.delete_selected')" 
                icon="o-trash" 
                class="btn-sm btn-error text-white font-bold rounded-lg" 
                :wire:confirm="__('notifications.ui.are_you_sure')"
                wire:click="deleteSelected" 
            />
        </x-slot:bulkActions>

        {{-- Table Cell Overrides --}}
        @scope('cell_title', $notification)
            <div class="flex gap-4 items-start py-2">
                <div @class([
                    'size-10 rounded-2xl flex items-center justify-center shrink-0',
                    'bg-primary/10 text-primary' => !$notification->is_read,
                    'bg-base-200 text-base-content/40' => $notification->is_read
                ])>
                    <x-mary-icon :name="$notification->is_read ? 'o-envelope-open' : 'o-envelope'" />
                </div>
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <span @class([
                            'text-sm font-bold',
                            'text-base-content' => !$notification->is_read,
                            'text-base-content/50' => $notification->is_read
                        ])>{{ $notification->title }}</span>
                        @if(!$notification->is_read)
                            <span class="size-2 rounded-full bg-error animate-pulse"></span>
                        @endif
                    </div>
                    <p @class([
                        'text-xs line-clamp-2',
                        'text-base-content/70' => !$notification->is_read,
                        'text-base-content/40' => $notification->is_read
                    ])>{{ $notification->message }}</p>
                </div>
            </div>
        @endscope

        @scope('cell_created_at', $notification)
            <span class="text-[10px] uppercase font-black tracking-widest opacity-40">
                {{ $notification->created_at->diffForHumans() }}
            </span>
        @endscope

        @scope('actions', $notification)
            <div class="flex justify-end gap-1">
                @if($notification->link)
                    <x-mary-button icon="o-arrow-top-right-on-square" class="btn-ghost btn-sm text-primary" link="{{ $notification->link }}" />
                @endif
                
                @if(!$notification->is_read)
                    <x-mary-button icon="o-check" class="btn-ghost btn-sm text-success" wire:click="markAsRead('{{ $notification->id }}')" />
                @endif
            </div>
        @endscope
    </x-layouts.manager>
</div>

<div class="p-8">
    <x-layouts.manager 
        :title="__('user.admin.title')" 
        :subtitle="__('user.admin.subtitle')" 
        :rows="$this->rows()" 
        :headers="$this->headers()"
        :selected-count="$this->selected_count"
        :sort-by="$sortBy"
    >
        {{-- Top Actions --}}
        <x-slot:actions>
            <x-mary-button :label="__('user.admin.new')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>

        {{-- Bulk Actions --}}
        <x-slot:bulkActions>
            <x-mary-button 
                :label="__('common.actions.delete_selected')" 
                icon="o-trash" 
                class="btn-sm btn-error text-white font-bold rounded-lg" 
                :wire:confirm="__('common.actions.confirm_action')"
                wire:click="deleteSelected" 
            />
        </x-slot:bulkActions>

        {{-- Table Cell Overrides --}}
        @scope('cell_name', $user)
            <div class="flex gap-4 items-center">
                <x-mary-avatar :title="$user->name" class="w-9 h-9" />
                <span class="text-sm font-bold">{{ $user->name }}</span>
            </div>
        @endscope

        @scope('actions', $user)
            <div class="flex justify-end gap-1">
                <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $user->id }}')" />
                @if($user->id !== auth()->id())
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:confirm="{{ __('common.actions.confirm_action') }}" wire:click="delete('{{ $user->id }}')" />
                @endif
            </div>
        @endscope
    </x-layouts.manager>

    {{-- Admin Modal --}}
    <x-mary-modal wire:model="userModal" :title="$userData['id'] ? __('user.admin.edit') : __('user.admin.new')" separator>
        <div class="grid grid-cols-1 gap-6 p-4">
            <x-mary-input :label="__('user.fields.full_name')" wire:model="userData.name" icon="o-user" class="rounded-xl border-base-300" />
            <x-mary-input :label="__('user.fields.email')" type="email" wire:model="userData.email" icon="o-envelope" class="rounded-xl border-base-300" />
            
            @if(!$userData['id'])
                <div class="alert alert-info shadow-sm rounded-xl">
                    <x-mary-icon name="o-information-circle" class="size-5" />
                    <span class="text-sm">{{ __('setup.wizard.username_notice') }}</span>
                </div>
            @endif
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('userModal', false)" class="rounded-xl" />
            <x-mary-button :label="__('user.admin.save')" type="submit" icon="o-check" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>

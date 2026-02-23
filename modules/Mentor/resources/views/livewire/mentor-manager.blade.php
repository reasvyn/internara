<div>
    <x-ui::header 
        wire:key="mentor-manager-header"
        :title="$title" 
        :subtitle="__('user::ui.manager.subtitle')"
        :context="'admin::ui.menu.mentors'"
    >
        <x-slot:actions wire:key="mentor-manager-actions">
            <div class="flex items-center gap-3 relative z-50">
                <x-ui::dropdown icon="tabler.dots" variant="tertiary" right>
                    <x-ui::menu-item title="ui::common.print" icon="tabler.printer" wire:click="printPdf" />
                    <x-ui::menu-item title="ui::common.export" icon="tabler.download" wire:click="exportCsv" />
                </x-ui::dropdown>

                <x-ui::dropdown 
                    :label="__('ui::common.bulk_actions')" 
                    icon="tabler.layers-intersect" 
                    variant="secondary" 
                    :disabled="count($selectedIds) === 0"
                >
                    <x-ui::menu-item 
                        title="ui::common.delete_selected" 
                        icon="tabler.trash" 
                        class="text-error" 
                        wire:click="removeSelected" 
                        wire:confirm="{{ __('ui::common.delete_confirm') }}"
                    />
                </x-ui::dropdown>

                <x-ui::button :label="__('user::ui.manager.add_mentor')" icon="tabler.plus" variant="primary" wire:click="add" />
            </div>
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card>
        <div 
            x-data="{ 
                search: $wire.entangle('search', true),
                applyFilter() {
                    let term = this.search.toLowerCase();
                    let rows = this.$el.querySelectorAll('table tbody tr:not(.mary-table-empty)');
                    rows.forEach(row => {
                        let text = row.innerText.toLowerCase();
                        row.style.display = text.includes(term) ? '' : 'none';
                    });
                }
            }" 
            x-init="$watch('search', () => applyFilter())"
        >
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input 
                        :placeholder="__('user::ui.manager.search_placeholder')" 
                        icon="tabler.search" 
                        wire:model.live.debounce.250ms="search" 
                        x-model="search"
                        clearable 
                    />
                </div>
            </div>

            <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh] relative">
                {{-- Instant Loading Overlay --}}
                <div wire:loading.flex wire:target="search" class="absolute inset-0 bg-base-100/60 backdrop-blur-[1px] z-20 items-center justify-center">
                    <span class="loading loading-spinner loading-md text-base-content/20"></span>
                </div>

                <x-mary-table 
                    class="table-zebra table-md"
                    :headers="[
                        ['key' => 'name', 'label' => __('user::ui.manager.table.name')],
                        ['key' => 'email', 'label' => __('user::ui.manager.table.email')],
                        ['key' => 'username', 'label' => __('user::ui.manager.table.username')],
                        ['key' => 'account_status', 'label' => __('user::ui.manager.table.status')],
                        ['key' => 'actions', 'label' => ''],
                    ]" 
                    :rows="$this->records" 
                    wire:model="selectedIds"
                    selectable
                    with-pagination
                >
                    @scope('cell_name', $user)
                        <div class="flex items-center gap-3">
                            <x-ui::avatar :image="$user->avatar_url" :title="$user->name" size="w-8" />
                            <div class="font-semibold">{{ $user->name }}</div>
                        </div>
                    @endscope

                    @scope('cell_account_status', $user)
                        @php
                            $statusName = $user->latestStatus()?->name ?? 'active';
                        @endphp
                        <x-ui::badge 
                            :value="__('user::ui.manager.form.' . $statusName)" 
                            :variant="$statusName === 'active' ? 'primary' : 'secondary'" 
                            class="badge-sm" 
                        />
                    @endscope

                    @scope('actions', $user)
                        <div class="flex justify-end gap-2">
                            <x-ui::button icon="tabler.edit" variant="tertiary" wire:click="edit('{{ $user->id }}')" class="text-info btn-xs" tooltip="{{ __('user::ui.manager.edit_mentor') }}" />
                            <x-ui::button 
                                icon="tabler.trash" 
                                variant="tertiary" 
                                wire:click="discard('{{ $user->id }}')" 
                                wire:confirm="{{ __('ui::common.delete_confirm') }}"
                                class="text-error btn-xs" 
                                tooltip="{{ __('ui::common.delete') }}" 
                            />
                        </div>
                    @endscope
                </x-mary-table>
            </div>
        </div>
    </x-ui::card>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" :title="$form->id ? __('user::ui.manager.edit_mentor') : __('user::ui.manager.add_mentor')">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('user::ui.manager.form.full_name')" icon="tabler.signature" wire:model="form.name" required />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input :label="__('user::ui.manager.form.email')" icon="tabler.mail" type="email" wire:model="form.email" required />
                @if($form->id)
                    <x-ui::input :label="__('user::ui.manager.form.username')" icon="tabler.at" wire:model="form.username" readonly />
                @endif
            </div>

            <x-ui::input 
                :label="__('user::ui.manager.form.password')" 
                icon="tabler.key"
                type="password" 
                wire:model="form.password" 
                :placeholder="$form->id ? __('user::ui.manager.form.password_hint') : ''" 
            />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input :label="__('user::ui.manager.form.phone')" icon="tabler.phone" wire:model="form.profile.phone" />
                <x-ui::select 
                    :label="__('user::ui.manager.form.gender')" 
                    icon="tabler.gender-intersex"
                    wire:model="form.profile.gender" 
                    :options="[
                        ['id' => 'male', 'name' => __('profile::enums.gender.male')],
                        ['id' => 'female', 'name' => __('profile::enums.gender.female')],
                    ]" 
                    :placeholder="__('user::ui.manager.form.select_gender')"
                />
            </div>

            <x-ui::textarea :label="__('user::ui.manager.form.address')" icon="tabler.map-pin" wire:model="form.profile.address" />

            <x-ui::select 
                :label="__('user::ui.manager.form.blood_type')" 
                icon="tabler.droplet"
                wire:model="form.profile.blood_type" 
                :options="[
                    ['id' => 'A', 'name' => 'A'],
                    ['id' => 'B', 'name' => 'B'],
                    ['id' => 'AB', 'name' => 'AB'],
                    ['id' => 'O', 'name' => 'O'],
                ]" 
                :placeholder="__('user::ui.manager.form.select_blood_type')"
            />

            <x-ui::select 
                :label="__('user::ui.manager.form.status')" 
                icon="tabler.circle-check"
                wire:model="form.status" 
                :options="[
                    ['id' => 'active', 'name' => __('user::ui.manager.form.active')],
                    ['id' => 'inactive', 'name' => __('user::ui.manager.form.inactive')],
                ]" 
            />

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('formModal', false)" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal wire:model="confirmModal" :title="__('user::ui.manager.delete.title')">
        <p>{{ __('user::ui.manager.delete.message') }}</p>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('confirmModal', false)" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>

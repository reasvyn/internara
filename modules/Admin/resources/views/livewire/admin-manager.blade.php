<div
    x-data="{ 
        search: $wire.entangle('search', true),
        selectedIds: $wire.entangle('selectedIds'),
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
    <x-ui::header 
        wire:key="admin-manager-header"
        :title="$title" 
        :subtitle="__('user::ui.manager.subtitle')"
    >
        <x-slot:actions wire:key="admin-manager-actions">
            <div class="flex items-center gap-3 relative z-50">
                <x-ui::dropdown icon="tabler.dots" variant="tertiary" right>
                    <x-ui::menu-item title="ui::common.print" icon="tabler.printer" wire:click="printPdf" />
                    <x-ui::menu-item title="ui::common.export" icon="tabler.download" wire:click="exportCsv" />
                </x-ui::dropdown>

                <x-ui::button :label="__('ui::common.refresh')" icon="tabler.refresh" variant="secondary" wire:click="refreshRecords" spinner="refreshRecords" />

                <div x-bind:class="{ 'pointer-events-none opacity-50': selectedIds.length === 0 }">
                    <x-ui::dropdown 
                        :label="__('ui::common.bulk_actions')" 
                        icon="tabler.layers-intersect" 
                        variant="secondary"
                        :disabled="count($this->selectedIds ?? []) === 0"
                    >
                        <x-ui::menu-item 
                            title="ui::common.delete_selected" 
                            icon="tabler.trash" 
                            class="text-error" 
                            wire:click="removeSelected" 
                            wire:confirm="{{ __('ui::common.delete_confirm') }}"
                        />
                    </x-ui::dropdown>
                </div>

                <x-ui::button :label="__('admin::ui.manager.add')" icon="tabler.plus" variant="primary" wire:click="add" />
            </div>
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card>
        <div>
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
                <div wire:loading.flex wire:target="search,refreshRecords" class="absolute inset-0 bg-base-100/60 backdrop-blur-[1px] z-20 items-center justify-center">
                    <span class="loading loading-spinner loading-md text-base-content/20"></span>
                </div>

                <x-mary-table 
                    class="table-zebra table-md"
                    :headers="[
                        ['key' => 'name', 'label' => __('user::ui.manager.table.name')],
                        ['key' => 'email', 'label' => __('user::ui.manager.table.email')],
                        ['key' => 'username', 'label' => __('user::ui.manager.table.username')],
                        ['key' => 'invitation_status', 'label' => __('admin::ui.manager.invitation_status')],
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

                    @scope('cell_invitation_status', $user)
                        @php
                            $invStatus = $user->invitation_status ?? 'not_invited';
                            $invVariant = match($invStatus) {
                                'accepted'    => 'success',
                                'pending'     => 'warning',
                                'expired'     => 'error',
                                default       => 'secondary',
                            };
                        @endphp
                        <x-ui::badge 
                            :value="__('admin::ui.manager.invitation_statuses.' . $invStatus)" 
                            :variant="$invVariant" 
                            class="badge-sm" 
                        />
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
                            @if(!$user->hasRole('super-admin'))
                                <x-ui::button
                                    icon="tabler.edit"
                                    variant="tertiary"
                                    wire:click="edit('{{ $user->id }}')"
                                    class="text-info btn-xs"
                                    tooltip="{{ __('user::ui.manager.edit_admin') }}"
                                />
                                @if($user->setup_required)
                                    <x-ui::button
                                        icon="tabler.mail-forward"
                                        variant="tertiary"
                                        wire:click="reinvite('{{ $user->id }}')"
                                        spinner="reinvite('{{ $user->id }}')"
                                        class="text-warning btn-xs"
                                        tooltip="{{ __('admin::ui.manager.reinvite_action') }}"
                                    />
                                @endif
                                <x-ui::button 
                                    icon="tabler.trash" 
                                    variant="tertiary" 
                                    wire:click="discard('{{ $user->id }}')" 
                                    wire:confirm="{{ __('ui::common.delete_confirm') }}"
                                    class="text-error btn-xs" 
                                    tooltip="{{ __('ui::common.delete') }}" 
                                />
                            @else
                                <x-ui::badge :value="__('System Protected')" variant="secondary" class="badge-sm opacity-50" />
                            @endif
                        </div>
                    @endscope
                </x-mary-table>
            </div>
        </div>
    </x-ui::card>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" :title="$form->id ? __('user::ui.manager.edit_admin') : __('admin::ui.manager.add')">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('user::ui.manager.form.full_name')" icon="tabler.signature" wire:model="form.name" required />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input :label="__('user::ui.manager.form.email')" icon="tabler.mail" type="email" wire:model="form.email" required />
                @if($form->id)
                    <x-ui::input :label="__('user::ui.manager.form.username')" icon="tabler.at" wire:model="form.username" readonly />
                @endif
            </div>

            @if(!$form->id)
                <x-ui::alert type="info" icon="tabler.mail-bolt" class="text-sm">
                    {{ __('admin::ui.manager.invitation_notice') }}
                </x-ui::alert>
            @endif

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
                <x-ui::button
                    :label="$form->id ? __('ui::common.save') : __('admin::ui.manager.invite_action')"
                    icon="tabler.mail-bolt"
                    type="submit"
                    variant="primary"
                    spinner="save"
                />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal wire:model="confirmModal" :title="__('user::ui.manager.delete.title')">
        <p>{{ __('admin::ui.manager.delete_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('confirmModal', false)" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>

<x-core::ui.record-manager :title="__('user.manager.title')" :subtitle="__('user.manager.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('user.manager.new')" icon="plus" color="primary" sm wire:click="createUser" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-ts-dropdown.items
            :text="__('common.actions.import')"
            icon="arrow-up-tray"
            @click="document.getElementById('import-csv').click()"
        />
        <input id="import-csv" type="file" accept=".csv" wire:model="importFile" class="hidden" />
        <x-ts-dropdown.items :text="__('common.actions.export')" icon="arrow-down-tray" wire:click="export" />
        <x-ts-dropdown.items
            :text="__('common.actions.template')"
            icon="document-arrow-down"
            wire:click="downloadTemplate"
        />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-core::widgets.stat-card
            icon="users"
            :title="__('user.manager.stats_total')"
            :value="$this->stats['total']"
            color="text-primary"
        />
        <x-core::widgets.stat-card
            icon="shield-check"
            :title="__('user.manager.stats_admins')"
            :value="$this->stats['admins']"
            color="text-secondary"
        />
        <x-core::widgets.stat-card
            icon="check-badge"
            :title="__('user.manager.stats_active')"
            :value="$this->stats['active']"
            color="text-success"
        />
        <x-core::widgets.stat-card
            icon="clock"
            :title="__('user.manager.stats_pending')"
            :value="$this->stats['pending']"
            color="text-warning"
        />
    </x-slot:stats>

    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('user.fields.roles') }}</label>
        <select wire:model.live="filters.role" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            @foreach ($this->roles as $role)
                <option value="{{ $role->name }}">{{ __('permission.role.'.$role->name) }}</option>
            @endforeach
        </select>

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('user.manager.status') }}</label>
        <select wire:model.live="filters.status" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            <option value="verified">{{ __('common.status.verified') }}</option>
            <option value="suspended">{{ __('user.manager.status_suspended') }}</option>
            <option value="provisioned">{{ __('user.manager.status_provisioned') }}</option>
            <option value="archived">{{ __('user.manager.status_archived') }}</option>
        </select>

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('user.manager.created_from') }}</label>
        <input
            wire:model.live="filters.created_from"
            type="date"
            class="input input-bordered input-sm w-full text-sm"
        />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('user.manager.created_to') }}</label>
        <input wire:model.live="filters.created_to" type="date" class="input input-bordered input-sm w-full text-sm" />
    </x-slot:filters>

    <x-slot:selectionBar>
        <x-ts-dropdown>
            <x-slot:trigger>
                <x-ts-button icon="chevron-down" color="primary" sm :text="__('common.actions.bulk_actions')" />
            </x-slot:trigger>
            <div class="w-52 p-1.5">
                <x-ts-dropdown.items
                    :text="__('user.manager.download_slips')"
                    icon="document-arrow-down"
                    wire:click="downloadSelectedSlips"
                />
                <hr class="border-base-content/10" />
                <x-ts-dropdown.items
                    :text="__('user.manager.lock_selected')"
                    icon="lock-closed"
                    class="text-warning"
                    wire:click="lockSelected"
                />
                <x-ts-dropdown.items
                    :text="__('user.manager.unlock_selected')"
                    icon="lock-open"
                    class="text-success"
                    wire:click="unlockSelected"
                />
                <hr class="border-base-content/10" />
                <x-ts-dropdown.items
                    :text="__('common.actions.export_selected')"
                    icon="arrow-down-tray"
                    wire:click="exportSelected"
                />
                <hr class="border-base-content/10" />
                <x-ts-dropdown.items
                    :text="__('common.actions.delete_selected')"
                    icon="trash"
                    class="text-error"
                    wire:click="askDeleteSelected"
                />
            </div>
        </x-ts-dropdown>
    </x-slot:selectionBar>

    <x-mary-table
        :headers="$this->headers()"
        :rows="$this->rows()"
        :sort-by="$sortBy"
        with-pagination
        selectable
        wire:model="selectedIds"
        class="table-sm"
    >
        @scope('cell_name', $user)
            <div class="flex items-center gap-3 py-1">
                <x-core::ui.avatar :user="$user" size="size-9" />
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">{{ $user->name }}</span>
                        @if ($user->hasRole('super_admin'))
                            <x-ts-icon
                                name="o-shield-check"
                                class="text-primary size-4"
                                :tooltip="__('user.manager.protected')"
                            />
                        @endif
                    </div>
                    <span class="text-base-content/50 text-xs">{{ $user->username }}</span>
                </div>
            </div>
        @endscope

        @scope('cell_email', $user)
            <span class="text-sm">{{ $user->email }}</span>
        @endscope

        @scope('cell_profile.phone', $user)
            <span class="text-base-content/60 text-sm">{{ $user->profile?->phone ?? '—' }}</span>
        @endscope

        @scope('cell_roles_list', $user)
            <div class="flex flex-wrap gap-1">
                @foreach ($user->roles as $role)
                    <span class="badge badge-sm badge-soft badge-primary text-[10px] font-medium">
                        {{ $role->name }}
                    </span>
                @endforeach
            </div>
        @endscope

        @scope('cell_status', $user)
            @php
                $status = $user->status?->value ?? 'unknown';
                $badgeClass = match ($status) {
                    'verified' => 'badge-success',
                    'suspended' => 'badge-warning',
                    'provisioned' => 'badge-info',
                    'archived' => 'badge-error',
                    'protected' => 'badge-primary',
                    default => 'badge-ghost',
                };
            @endphp
            <span class="badge badge-sm {{ $badgeClass }} font-medium text-[10px]">
                {{ __("user.manager.status_{$status}") }}
            </span>
        @endscope

        @scope('actions', $user)
            @if ($user->hasRole('super_admin'))
                <div class="flex justify-end">
                    <span class="text-base-content/40 text-xs italic">{{ __('user.manager.protected') }}</span>
                </div>
            @else
                <div class="flex justify-end gap-1">
                    <x-ts-button
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="editUser('{{ $user->id }}')"
                        :aria-label="__('common.actions.edit')"
                    />
                    <x-ts-button
                        icon="shield-check"
                        class="text-warning"
                        color="white"
                        sm
                        wire:click="askChangeStatus('{{ $user->id }}')"
                        :aria-label="__('user.manager.change_status')"
                    />
                    @if (in_array($user->status?->value, ['verified', 'suspended']))
                        <x-ts-button
                            icon="arrow-path"
                            color="white"
                            sm
                            wire:click="toggleStatus('{{ $user->id }}')"
                            :aria-label="__('user.manager.toggle_status')"
                            :tooltip="__('user.manager.toggle_status')"
                            spinner="toggleStatus"
                        />
                    @endif
                    <x-ts-button
                        icon="key"
                        class="text-primary"
                        color="white"
                        sm
                        wire:click="showSlip('{{ $user->id }}')"
                        :aria-label="__('user.manager.account_slip')"
                    />
                    @if ($user->id !== auth()->id())
                        <x-ts-button
                            icon="trash"
                            class="text-error"
                            color="white"
                            sm
                            wire:click="askDeleteUser('{{ $user->id }}')"
                            :aria-label="__('common.actions.delete')"
                        />
                    @endif
                </div>
            @endif
        @endscope
    </x-mary-table>

    {{-- Status Modal --}}
    <x-ts-modal wire="showStatusModal" :title="__('user.manager.change_status')" separator blur>
        <form wire:submit="changeStatus" class="space-y-5">
            <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                <x-ts-select
                    :label="__('user.manager.new_status')"
                    wire:model="selectedStatus"
                    :options="$this->statusOptions"
                    icon="flag"
                />
                <x-ts-textarea
                    :label="__('user.manager.status_reason')"
                    wire:model="statusReason"
                    :placeholder="__('user.manager.status_reason_placeholder')"
                    rows="2"
                    icon="document-text"
                    class="mt-4"
                />
            </div>

            <x-slot:actions>
                <x-ts-button
                    :text="__('common.actions.cancel')"
                    wire:click="$set('showStatusModal', false)"
                    color="white"
                    sm
                />
                <x-ts-button
                    :text="__('user.manager.change_status')"
                    color="primary"
                    sm
                    type="submit"
                    spinner="changeStatus"
                />
            </x-slot:actions>
        </form>
    </x-ts-modal>

    <x-slot:modal>
        <x-ts-modal
            wire="userModal"
            :title="$form->id ? __('user.manager.edit') : __('user.manager.new')"
            separator
            blur
        >
            <form wire:submit="saveUser" class="space-y-5">
                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('user.manager.account') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-input :label="__('user.fields.full_name')" wire:model="form.name" icon="user" />
                        <x-ts-input
                            :label="__('user.fields.email')"
                            type="email"
                            wire:model="form.email"
                            icon="envelope"
                        />
                    </div>
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('user.manager.profile') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-input :label="__('user.fields.phone')" wire:model="form.phone" icon="phone" />
                        <x-ts-select
                            :label="__('user.fields.gender')"
                            wire:model="form.gender"
                            :options="[['id' => 'L', 'name' => __('common.male')], ['id' => 'P', 'name' => __('common.female')]]"
                        />
                        <x-ts-input :label="__('user.fields.pob')" wire:model="form.pob" icon="map-pin" />
                        <x-ts-input :label="__('user.fields.dob')" type="date" wire:model="form.dob" icon="calendar" />
                        <x-ts-input
                            :label="__('user.fields.address')"
                            wire:model="form.address"
                            class="md:col-span-2"
                            icon="map-pin"
                        />
                        <x-ts-textarea
                            :label="__('user.fields.bio')"
                            wire:model="form.bio"
                            rows="2"
                            class="md:col-span-2"
                            icon="document-text"
                        />
                    </div>
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('user.fields.emergency') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-input
                            :label="__('user.fields.emergency_contact_name')"
                            wire:model="form.emergency_contact_name"
                            icon="user"
                        />
                        <x-ts-input
                            :label="__('user.fields.emergency_contact_phone')"
                            wire:model="form.emergency_contact_phone"
                            icon="phone"
                        />
                        <x-ts-input
                            :label="__('user.fields.emergency_contact_address')"
                            wire:model="form.emergency_contact_address"
                            class="md:col-span-2"
                            icon="map-pin"
                        />
                    </div>
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('user.fields.roles') }}
                    </p>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($this->roles as $role)
                            <x-ts-checkbox
                                :label="__('permission.role.'.$role->name)"
                                wire:model="form.roles"
                                value="{{ $role->name }}"
                            />
                        @endforeach
                    </div>
                </div>

                <x-slot:actions>
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('userModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('user.manager.save')" color="primary" sm type="submit" spinner="saveUser" />
                </x-slot:actions>
            </form>
        </x-ts-modal>
    </x-slot:modal>

    @include('user.user-management.components.account-slip-modal')
    @include('user.user-management.components.user-guide')

    <x-core::ui.confirm :message="__('common.actions.confirm_action')" />
</x-core::ui.record-manager>

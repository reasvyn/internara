<x-shared::ui.record-manager
    :title="__('user.manager.title')"
    :subtitle="__('user.manager.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('user.manager.new')" icon="o-plus" class="btn-primary btn-sm" wire:click="createUser" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.import')" icon="o-arrow-up-tray" onclick="document.getElementById('import-csv').click()" />
        <input id="import-csv" type="file" accept=".csv" wire:model="importFile" class="hidden" />
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" wire:click="export" />
        <x-mary-menu-item :title="__('common.actions.template')" icon="o-document-arrow-down" wire:click="downloadTemplate" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-shared::widgets.stat-card icon="o-users" :title="__('user.manager.stats_total')" :value="$this->stats['total']" />
        <x-shared::widgets.stat-card icon="o-shield-check" :title="__('user.manager.stats_admins')" :value="$this->stats['admins']" />
        <x-shared::widgets.stat-card icon="o-check-badge" :title="__('user.manager.stats_active')" :value="$this->stats['active']" />
        <x-shared::widgets.stat-card icon="o-clock" :title="__('user.manager.stats_pending')" :value="$this->stats['pending']" />
    </x-slot:stats>

    <x-slot:filters>
        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.fields.roles') }}</label>
        <select wire:model.live="filters.role" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            @foreach($this->roles as $role)
                <option value="{{ $role->name }}">{{ $role->name }}</option>
            @endforeach
        </select>

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.manager.status') }}</label>
        <select wire:model.live="filters.status" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            <option value="verified">{{ __('common.status.verified') }}</option>
            <option value="suspended">{{ __('user.manager.status_suspended') }}</option>
            <option value="provisioned">{{ __('user.manager.status_provisioned') }}</option>
            <option value="archived">{{ __('user.manager.status_archived') }}</option>
        </select>

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.manager.created_from') }}</label>
        <input wire:model.live="filters.created_from" type="date" class="input input-bordered input-sm w-full text-sm" />

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('user.manager.created_to') }}</label>
        <input wire:model.live="filters.created_to" type="date" class="input input-bordered input-sm w-full text-sm" />
    </x-slot:filters>

    <x-shared::ui.selection-bar>
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" :label="__('common.actions.bulk_actions')" />
            </x-slot:trigger>
            <div class="p-1.5 w-52">
                <x-mary-menu-item :title="__('user.manager.download_slips')" icon="o-document-arrow-down" wire:click="downloadSelectedSlips" />
                <hr class="border-base-content/10" />
                <x-mary-menu-item :title="__('common.actions.export_selected')" icon="o-arrow-down-tray" wire:click="exportSelected" />
                <hr class="border-base-content/10" />
                <x-mary-menu-item :title="__('common.actions.delete_selected')" icon="o-trash" class="text-error"
                    wire:confirm="{{ __('common.actions.confirm_action') }}" wire:click="deleteSelected" />
            </div>
        </x-mary-dropdown>
    </x-shared::ui.selection-bar>

    <div class="overflow-x-auto">
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
                    <x-shared::ui.avatar :user="$user" size="size-9" />
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm">{{ $user->name }}</span>
                            @if($user->hasRole('super_admin'))
                                <x-mary-icon name="o-shield-check" class="size-4 text-primary" tooltip="Protected" />
                            @endif
                        </div>
                        <span class="text-xs text-base-content/50">{{ $user->username }}</span>
                    </div>
                </div>
            @endscope

            @scope('cell_email', $user)
                <span class="text-sm">{{ $user->email }}</span>
            @endscope

            @scope('cell_roles_list', $user)
                <div class="flex flex-wrap gap-1">
                    @foreach($user->roles as $role)
                        <span class="badge badge-sm badge-soft badge-primary font-medium text-[10px]">
                            {{ $role->name }}
                        </span>
                    @endforeach
                </div>
            @endscope

            @scope('cell_status', $user)
                @php
                    $status = $user->latestStatus()?->name ?? 'unknown';
                    $badgeClass = match($status) {
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
                @if($user->hasRole('super_admin'))
                    <div class="flex justify-end">
                        <span class="text-xs text-base-content/40 italic">{{ __('user.manager.protected') }}</span>
                    </div>
                @else
                    <div class="flex justify-end gap-1">
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="editUser('{{ $user->id }}')" :aria-label="__('common.actions.edit')" />
                        <x-mary-button icon="o-shield-check" class="btn-ghost btn-sm text-warning" wire:click="askChangeStatus('{{ $user->id }}')" :aria-label="__('user.manager.change_status')" />
                        <x-mary-button icon="o-key" class="btn-ghost btn-sm text-info" wire:click="resetPassword('{{ $user->id }}')" :aria-label="__('user.manager.reset_password')" />
                        <x-mary-button icon="o-document-arrow-down" class="btn-ghost btn-sm text-primary" wire:click="downloadAccountSlip('{{ $user->id }}')" :aria-label="__('user.manager.download_slip')" />
                        @if($user->id !== auth()->id())
                            <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:confirm="{{ __('common.actions.confirm_action') }}" wire:click="deleteUser('{{ $user->id }}')" :aria-label="__('common.actions.delete')" />
                        @endif
                    </div>
                @endif
            @endscope
        </x-mary-table>
    </div>

    {{-- Status Modal --}}
    <x-mary-modal wire:model="showStatusModal" :title="__('user.manager.change_status')" separator class="backdrop-blur-sm">
        <x-mary-form wire:submit="changeStatus" class="space-y-5">
            <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                <x-mary-select :label="__('user.manager.new_status')" wire:model="selectedStatus" :options="$this->statusOptions" icon="o-flag" />
                <x-mary-textarea :label="__('user.manager.status_reason')" wire:model="statusReason" :placeholder="__('user.manager.status_reason_placeholder')" rows="2" icon="o-document-text" class="mt-4" />
            </div>

            <x-slot:actions>
                <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showStatusModal', false)" class="btn-ghost btn-sm" />
                <x-mary-button :label="__('user.manager.change_status')" class="btn-primary btn-sm" type="submit" spinner="changeStatus" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>

    <x-slot:modal>
        <x-mary-modal wire:model="userModal" :title="$form->id ? __('user.manager.edit') : __('user.manager.new')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="saveUser" class="space-y-5">
                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('user.manager.account') }}</p>
                    <x-mary-input :label="__('user.fields.full_name')" wire:model="form.name" icon="o-user" />
                    <x-mary-input :label="__('user.fields.email')" type="email" wire:model="form.email" icon="o-envelope" />
                </div>

                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('user.fields.roles') }}</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach($this->roles as $role)
                            <x-mary-checkbox
                                :label="$role->name"
                                wire:model="form.roles"
                                value="{{ $role->name }}"
                            />
                        @endforeach
                    </div>
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('userModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('user.manager.save')" class="btn-primary btn-sm" type="submit" spinner="saveUser" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-shared::ui.record-manager>

<div>
    <x-ui::header 
        :title="$title" 
        :subtitle="__('user::ui.manager.subtitle')"
        :context="'user::ui.' . ($roleKey ?? 'user') . '_management'"
    >
        <x-slot:actions>
            <x-ui::button :label="__('user::ui.manager.add_' . $roleKey)" icon="tabler.plus" variant="primary" wire:click="add" />
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card>
        <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="w-full md:w-1/3">
                <x-ui::input :placeholder="__('user::ui.manager.search_placeholder')" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
            </div>
        </div>

        <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh]">
            <x-mary-table 
                class="table-zebra table-md"
                :headers="[
                    ['key' => 'name', 'label' => __('user::ui.manager.table.name')],
                    ['key' => 'email', 'label' => __('user::ui.manager.table.email')],
                    ['key' => 'username', 'label' => __('user::ui.manager.table.username')],
                    ['key' => 'roles', 'label' => __('user::ui.manager.table.roles')],
                    ['key' => 'account_status', 'label' => __('user::ui.manager.table.status')],
                    ['key' => 'actions', 'label' => ''],
                ]" 
                :rows="$this->records" 
                with-pagination
            >
                @scope('cell_name', $user)
                    <div class="flex items-center gap-3">
                        <x-ui::avatar :image="$user->avatar_url" :title="$user->name" size="w-8" />
                        <div class="font-semibold">{{ $user->name }}</div>
                    </div>
                @endscope

                @scope('cell_roles', $user)
                    <div class="flex flex-wrap gap-1">
                        @foreach($user->roles as $role)
                            <livewire:permission::role-badge :role="$role" size="xs" wire:key="role-{{ $user->id }}-{{ $role->id }}" />
                        @endforeach
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
                        @if(!$user->hasRole('super-admin'))
                            <x-ui::button icon="tabler.edit" variant="tertiary" wire:click="edit('{{ $user->id }}')" class="text-info" tooltip="{{ __('user::ui.manager.edit_' . $roleKey) }}" />
                            <x-ui::button 
                                icon="tabler.trash" 
                                variant="tertiary" 
                                wire:click="discard('{{ $user->id }}')" 
                                wire:confirm="{{ __('ui::common.delete_confirm') }}"
                                class="text-error" 
                                tooltip="{{ __('ui::common.delete') }}" 
                            />
                        @else
                            <x-ui::badge :value="__('System Protected')" variant="secondary" class="badge-sm opacity-50" />
                        @endif
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-ui::card>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" :title="$form->id ? __('user::ui.manager.edit_' . $roleKey) : __('user::ui.manager.add_' . $roleKey)">
        <x-ui::form wire:submit="save">
            <x-ui::input :label="__('user::ui.manager.form.full_name')" wire:model="form.name" required />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input :label="__('user::ui.manager.form.email')" type="email" wire:model="form.email" required />
                <x-ui::input :label="__('user::ui.manager.form.username')" wire:model="form.username" required />
            </div>

            <x-ui::input 
                :label="__('user::ui.manager.form.password')" 
                type="password" 
                wire:model="form.password" 
                :placeholder="$form->id ? __('user::ui.manager.form.password_hint') : ''" 
            />

            {{-- Role-Specific Profile Fields --}}
            @if($targetRole === 'student')
                <x-ui::input :label="__('user::ui.manager.form.identity_number')" wire:model="form.profile.identity_number" placeholder="Nomor Induk Siswa Nasional" />
            @elseif($targetRole === 'teacher')
                <x-ui::input :label="__('user::ui.manager.form.identity_number')" wire:model="form.profile.identity_number" placeholder="Nomor Induk Pegawai" />
            @endif

            @if(in_array($targetRole, ['student', 'teacher']))
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui::select 
                        :label="__('user::ui.manager.form.department')" 
                        wire:model="form.profile.department_id" 
                        :options="$this->departments" 
                        :placeholder="__('user::ui.manager.form.select_department')"
                    />
                    <x-ui::input :label="__('user::ui.manager.form.phone')" wire:model="form.profile.phone" />
                </div>
            @endif

            @if(!$targetRole)
                <x-ui::choices
                    :label="__('user::ui.manager.form.roles')"
                    wire:model="form.roles"
                    :options="[
                        ['id' => 'student', 'name' => __('permission::roles.student')],
                        ['id' => 'teacher', 'name' => __('permission::roles.teacher')],
                        ['id' => 'mentor', 'name' => __('permission::roles.mentor')],
                        ['id' => 'admin', 'name' => __('permission::roles.admin')],
                    ]"
                />
            @endif

            <x-ui::select 
                :label="__('user::ui.manager.form.status')" 
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

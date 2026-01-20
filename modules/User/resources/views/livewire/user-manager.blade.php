<div>
    <x-ui::header :title="$title" subtitle="{{ __('Manage platform users and roles.') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('Add User') }}" icon="tabler.plus" class="btn-primary" wire:click="add" />
        </x-slot:actions>
    </x-ui::header>

    <x-ui::main>
        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input placeholder="{{ __('Search users...') }}" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
            </div>

            <x-ui::table :headers="[
                ['key' => 'name', 'label' => __('Name')],
                ['key' => 'email', 'label' => __('Email')],
                ['key' => 'username', 'label' => __('Username')],
                ['key' => 'roles', 'label' => __('Roles')],
                ['key' => 'status', 'label' => __('Status')],
            ]" :rows="$this->records" with-pagination>
                @scope('cell_name', $user)
                    <div class="flex items-center gap-3">
                        <x-ui::avatar :image="$user->avatar_url" class="w-8 h-8" />
                        <div class="font-semibold">{{ $user->name }}</div>
                    </div>
                @endscope

                @scope('cell_roles', $user)
                    <div class="flex flex-wrap gap-1">
                        @foreach($user->roles as $role)
                            <x-ui::badge :label="ucfirst($role->name)" class="badge-outline badge-xs" />
                        @endforeach
                    </div>
                @endscope

                @scope('cell_status', $user)
                    @php
                        $statusName = $user->latestStatus()?->name ?? 'active';
                    @endphp
                    <x-ui::badge :label="ucfirst($statusName)" 
                        class="{{ $statusName === 'active' ? 'badge-success' : 'badge-ghost' }} badge-sm" />
                @endscope

                @scope('actions', $user)
                    <div class="flex gap-2">
                        <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $user->id }}')" tooltip="{{ __('Edit User') }}" />
                        @if(!$user->hasRole('super-admin'))
                            <x-ui::button icon="tabler.trash" class="btn-ghost btn-sm text-error" wire:click="discard('{{ $user->id }}')" tooltip="{{ __('Delete User') }}" />
                        @endif
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal wire:model="formModal" title="{{ $form->id ? __('Edit User') : __('Add User') }}">
        <x-ui::form wire:submit="save">
            <x-ui::input label="{{ __('Full Name') }}" wire:model="form.name" required />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input label="{{ __('Email') }}" type="email" wire:model="form.email" required />
                <x-ui::input label="{{ __('Username') }}" wire:model="form.username" required />
            </div>

            <x-ui::input label="{{ __('Password') }}" type="password" wire:model="form.password" 
                placeholder="{{ $form->id ? __('Leave blank to keep current password') : '' }}" />

            @if(!$targetRole)
                <x-ui::choices
                    label="{{ __('Roles') }}"
                    wire:model="form.roles"
                    :options="[
                        ['id' => 'student', 'name' => 'Student'],
                        ['id' => 'teacher', 'name' => 'Teacher'],
                        ['id' => 'mentor', 'name' => 'Mentor'],
                        ['id' => 'admin', 'name' => 'Admin'],
                    ]"
                />
            @endif

            <x-ui::select 
                label="{{ __('Status') }}" 
                wire:model="form.status" 
                :options="[
                    ['id' => 'active', 'name' => 'Active'],
                    ['id' => 'inactive', 'name' => 'Inactive'],
                ]" 
            />

            <x-slot:actions>
                <x-ui::button label="{{ __('Cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('Save') }}" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal wire:model="confirmModal" title="{{ __('Confirm Deletion') }}">
        <p>{{ __('Are you sure you want to delete this user? This action cannot be undone.') }}</p>
        <x-slot:actions>
            <x-ui::button label="{{ __('Cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button label="{{ __('Delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>

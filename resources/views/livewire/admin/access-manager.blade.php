<div>
    <x-mary-header title="Access Management" subtitle="Manage roles and their granular permissions" separator />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($roles as $role)
            <x-mary-card title="{{ $role->name }}" subtitle="{{ $role->users_count }} users assigned" separator>
                <div class="text-sm opacity-70 mb-4">
                    {{ $role->permissions_count }} permissions granted.
                </div>
                
                <x-slot:actions>
                    <x-mary-button label="Manage Permissions" icon="o-shield-check" wire:click="editRolePermissions('{{ $role->id }}')" class="btn-sm btn-ghost" />
                </x-slot:actions>
            </x-mary-card>
        @endforeach
    </div>

    <x-mary-modal wire:model="roleModal" title="Manage Permissions: {{ $selectedRole?->name }}" separator class="backdrop-blur">
        <x-mary-form wire:submit="savePermissions">
            <div class="max-h-[60vh] overflow-y-auto px-1">
                <div class="grid grid-cols-1 gap-4">
                    @foreach($permissions as $permission)
                        <x-mary-checkbox 
                            :label="$permission->name" 
                            wire:model="selectedPermissions" 
                            :value="$permission->name" />
                    @endforeach
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" wire:click="$set('roleModal', false)" />
                <x-mary-button label="Save Permissions" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>

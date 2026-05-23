<div>
    <x-mary-header :title="__('auth.access_management.title')" :subtitle="__('auth.access_management.subtitle')" separator />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($roles as $role)
            <x-mary-card title="{{ $role->name }}" :subtitle="__('auth.access_management.users_assigned', ['count' => $role->users_count])" separator>
                <div class="text-sm opacity-70 mb-4">
                    {{ __('auth.access_management.permissions_granted', ['count' => $role->permissions_count]) }}
                </div>
                
                <x-slot:actions>
                    <x-mary-button :label="__('auth.access_management.manage_permissions')" icon="o-shield-check" wire:click="editRolePermissions('{{ $role->id }}')" class="btn-sm btn-ghost" />
                </x-slot:actions>
            </x-mary-card>
        @endforeach
    </div>

    <x-mary-modal wire:model="roleModal" :title="__('auth.access_management.manage_title', ['name' => $selectedRole?->name])" separator class="backdrop-blur">
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
                <x-mary-button :label="__('auth.access_management.cancel')" wire:click="$set('roleModal', false)" />
                <x-mary-button :label="__('auth.access_management.save')" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>

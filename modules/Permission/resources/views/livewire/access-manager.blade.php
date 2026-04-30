<div class="space-y-8">
    {{-- Role Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        @foreach($this->getRecords() as $role)
            <div class="card bg-base-200/50 hover:bg-base-200 transition-all duration-300 border border-base-300/50 hover:border-primary/30">
                <div class="card-body p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-bold text-base text-base-content">
                                {{ $role['name'] }}
                            </h3>
                            <p class="text-xs text-base-content/60 mt-1 line-clamp-2">
                                {{ $role['description'] }}
                            </p>
                        </div>
                        @if($role['manageable'])
                            <x-ui::badge variant="success" class="text-[10px]">
                                {{ __('permission::ui.access_manager.manageable') }}
                            </x-ui::badge>
                        @endif
                    </div>

                    <div class="flex items-center justify-between text-xs text-base-content/60 mt-3">
                        <div class="flex items-center gap-1">
                            <x-ui::icon name="tabler-key" class="size-3" />
                            <span>{{ $role['permission_count'] }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <x-ui::icon name="tabler-users" class="size-3" />
                            <span>{{ $role['user_count'] }}</span>
                        </div>
                    </div>

                    @if($role['manageable'])
                        <div class="card-actions mt-2">
                            <button 
                                class="btn btn-primary btn-xs btn-block"
                                wire:click="openManagePermissions('{{ $role['id'] }}')"
                            >
                                <x-ui::icon name="tabler-key" class="size-3" />
                                {{ __('permission::ui.access_manager.manage') }}
                            </button>
                        </div>
                    @else
                        <div class="text-[10px] text-base-content/40 mt-2 text-center">
                            {{ __('permission::ui.access_manager.no_access') }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Permission Modal --}}
    @if($this->modalOpen)
        <x-ui::modal wire:modal="{{ $this->modalOpen }}" class="modal-backdrop">
            <div class="modal-box bg-base-100 max-w-5xl w-full max-h-[90vh]">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-bold text-lg">
                            {{ __('permission::ui.access_manager.modal.title') }}
                        </h3>
                        <p class="text-sm text-base-content/60">
                            {{ $this->selectedRecord?->name }}
                        </p>
                    </div>
                    <button class="btn btn-ghost btn-sm btn-circle" wire:click="{{ $this->modalCloseMethod }}">
                        <x-ui::icon name="tabler-x" />
                    </button>
                </div>

                <div class="divider"></div>

                @if($this->selectedRecord && $this->canManageRole($this->selectedRecord->name))
                    @php($rolePerms = $this->getCurrentPermissions($this->selectedRecord->id))
                    <div class="overflow-y-auto max-h-[65vh] space-y-4">
                        @foreach($this->permissionsByModule() as $module => $modulePerms)
                            <div>
                                <h4 class="font-bold text-sm uppercase tracking-wider text-base-content/70 mb-2 sticky top-0 bg-base-100 py-1">
                                    {{ $module }}
                                </h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                    @foreach($modulePerms as $perm)
                                        <label class="flex items-center gap-2 cursor-pointer hover:bg-base-200 p-2 rounded">
                                            <input 
                                                type="checkbox" 
                                                class="checkbox checkbox-primary checkbox-sm"
                                                wire:change="togglePermission('{{ $this->selectedRecord->id }}', '{{ $perm }}')"
                                                {{ in_array($perm, $rolePerms) ? 'checked' : '' }}
                                            />
                                            <span class="text-sm select-all">{{ $perm }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="modal-action">
                        <button class="btn btn-ghost" wire:click="{{ $this->modalCloseMethod }}">
                            {{ __('ui::common.close') }}
                        </button>
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-ui::icon name="tabler-lock" class="size-12 mx-auto text-base-content/30 mb-3" />
                        <p class="text-base-content/60">
                            {{ __('permission::ui.access_manager.cannot_manage') }}
                        </p>
                    </div>
                @endif
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </x-ui::modal>
    @endif
</div>
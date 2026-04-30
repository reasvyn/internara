{{-- Viewer-only actions for UserManager general view --}}
{{-- SuperAdmin: delete allowed. Admin: read-only (no actions). --}}
<div class="flex justify-end gap-2">
    @if($isSuperAdmin)
        @if(!$user->hasRole('super-admin'))
            <x-ui::button
                icon="tabler.trash"
                variant="tertiary"
                wire:click="discard('{{ $user->id }}')"
                wire:confirm="{{ __('ui::common.delete_confirm') }}"
                class="text-error btn-xs"
                tooltip="{{ __('ui::common.delete') }}"
            />
        @else
            <x-ui::badge :value="__('user::ui.viewer.system_protected')" variant="secondary" class="badge-sm opacity-50" />
        @endif
    @endif
</div>

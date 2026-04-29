<div class="flex justify-end gap-2">
    @if(!$user->hasRole('super-admin'))
        <x-ui::button
            icon="tabler.mail-share"
            variant="tertiary"
            wire:click="sendPasswordResetLink('{{ $user->id }}')"
            class="text-warning btn-xs"
            tooltip="{{ __('user::ui.manager.form.send_setup_link') }}"
        />
        <x-ui::button
            icon="tabler.edit"
            variant="tertiary"
            wire:click="edit('{{ $user->id }}')"
            class="text-info btn-xs"
            tooltip="{{ __('user::ui.manager.edit_' . $roleKey) }}"
        />
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

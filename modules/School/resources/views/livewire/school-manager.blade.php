<div class="flex size-full flex-col items-center justify-center">
    <x-ui::card
        class="w-full max-w-prose text-center"
        title="{{ __('school::ui.title') }}"
        subtitle="{{ __('school::ui.subtitle') }}"
    >
        <x-ui::form class="flex w-full flex-col gap-8" wire:submit="save">
            <div class="grid grid-cols-1 gap-4 text-start lg:grid-cols-2">
                <div class="col-span-full">
                    <x-ui::file
                        label="{{ __('school::ui.logo') }}"
                        wire:model="form.logo_file"
                        accept="image/*"
                        crop-after-change
                        :preview="$form->logo_url"
                        hint="{{ __('school::ui.logo_hint') }}"
                    />
                </div>

                <div class="col-span-full">
                    <x-ui::input
                        type="text"
                        label="{{ __('school::ui.name') }}"
                        placeholder="{{ __('school::ui.name') }}"
                        required
                        autofocus
                        wire:model="form.name"
                    />
                </div>

                <div class="col-span-full">
                    <x-ui::textarea
                        label="{{ __('school::ui.address') }}"
                        placeholder="{{ __('school::ui.address') }}"
                        wire:model="form.address"
                    />
                </div>

                <div class="col-span-full">
                    <x-ui::input
                        type="email"
                        label="{{ __('school::ui.email') }}"
                        placeholder="{{ __('school::ui.email') }}"
                        wire:model="form.email"
                    />
                </div>

                <x-ui::input
                    type="tel"
                    label="{{ __('school::ui.phone') }}"
                    placeholder="{{ __('school::ui.phone') }}"
                    wire:model="form.phone"
                />

                <x-ui::input
                    type="tel"
                    label="{{ __('school::ui.fax') }}"
                    placeholder="{{ __('school::ui.fax') }}"
                    wire:model="form.fax"
                />

                <div class="col-span-full">
                    <x-ui::input
                        type="text"
                        label="{{ __('school::ui.principal_name') }}"
                        placeholder="{{ __('school::ui.principal_name') }}"
                        wire:model="form.principal_name"
                    />
                </div>
            </div>

            <div class="flex flex-col items-center gap-8">
                <x-ui::button class="btn-primary w-full" label="{{ __('shared::ui.save') }}" type="submit" />
            </div>
        </x-ui::form>
    </x-ui::card>
</div>

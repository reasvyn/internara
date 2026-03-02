<div>
    <x-ui::header 
        :title="__('school::ui.title')" 
        :subtitle="__('school::ui.subtitle')"
        :context="'school::ui.title'"
    />

    <div class="flex size-full flex-col items-center justify-center">
        <x-ui::card
            wire:key="school-manager-card"
            class="w-full max-w-prose text-center"
        >
        <x-ui::form class="flex w-full flex-col gap-8" wire:submit="save">
            <div class="grid grid-cols-1 gap-4 text-start lg:grid-cols-2">
                <div class="col-span-full" wire:key="sm-logo">
                    <x-ui::file
                        :label="__('school::ui.logo')"
                        wire:model="form.logo_file"
                        accept="image/*"
                        crop
                        ratio="1/1"
                        :preview="$form->logo_url"
                        :hint="__('school::ui.logo_hint')"
                    />
                </div>

                <div class="col-span-full" wire:key="sm-name">
                    <x-ui::input
                        type="text"
                        :label="__('school::ui.name')"
                        :placeholder="__('school::ui.name')"
                        icon="tabler.school"
                        required
                        autofocus
                        wire:model="form.name"
                    />
                </div>

                <div class="col-span-full" wire:key="sm-code">
                    <x-ui::input
                        type="text"
                        :label="__('school::ui.institutional_code')"
                        :placeholder="__('school::ui.institutional_code')"
                        icon="tabler.id"
                        required
                        wire:model="form.institutional_code"
                    />
                </div>

                <div class="col-span-full" wire:key="sm-address">
                    <x-ui::textarea
                        :label="__('school::ui.address')"
                        :placeholder="__('school::ui.address')"
                        icon="tabler.map-pin"
                        wire:model="form.address"
                    />
                </div>

                <div class="col-span-full" wire:key="sm-email">
                    <x-ui::input
                        type="email"
                        :label="__('school::ui.email')"
                        :placeholder="__('school::ui.email')"
                        icon="tabler.mail"
                        wire:model="form.email"
                    />
                </div>

                <div wire:key="sm-phone">
                    <x-ui::input
                        type="tel"
                        :label="__('school::ui.phone')"
                        :placeholder="__('school::ui.phone')"
                        icon="tabler.phone"
                        wire:model="form.phone"
                    />
                </div>

                <div wire:key="sm-fax">
                    <x-ui::input
                        type="tel"
                        :label="__('school::ui.fax')"
                        :placeholder="__('school::ui.fax')"
                        icon="tabler.printer"
                        wire:model="form.fax"
                    />
                </div>

                <div class="col-span-full" wire:key="sm-principal">
                    <x-ui::input
                        type="text"
                        :label="__('school::ui.principal_name')"
                        :placeholder="__('school::ui.principal_name')"
                        icon="tabler.user-star"
                        wire:model="form.principal_name"
                    />
                </div>
            </div>

            <div class="flex flex-col items-center gap-8" wire:key="sm-actions">
                <x-ui::button variant="primary" class="w-full" :label="__('ui::common.save')" type="submit" />
            </div>
        </x-ui::form>
    </x-ui::card>
</div>
<div class="max-w-2xl mx-auto py-8">
    <x-ui::header
        :title="__('internship::ui.registration_title')"
        :subtitle="__('internship::ui.registration_subtitle')"
    />

    <x-ui::card>
        <x-ui::form wire:submit="submit">
            <div class="space-y-6">
                {{-- Program Selection --}}
                <x-ui::select
                    :label="__('internship::ui.program')"
                    icon="tabler.presentation"
                    wire:model.live="form.internship_id"
                    :options="$this->programs"
                    :placeholder="__('ui::common.select')"
                    required
                />

                @if($form->internship_id)
                    {{-- Placement Selection --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="label-text font-semibold">{{ __('internship::ui.placement') }}</label>
                            <label class="label cursor-pointer gap-2">
                                <span class="label-text text-sm">{{ __('internship::ui.propose_new_partner') }}</span>
                                <input type="checkbox" class="toggle toggle-primary toggle-sm" wire:model.live="proposeNewPartner" />
                            </label>
                        </div>

                        @if(!$proposeNewPartner)
                            <x-ui::select
                                wire:model="form.placement_id"
                                :options="$this->availablePlacements"
                                :placeholder="__('ui::common.select')"
                                required
                            />
                        @else
                            <div class="space-y-4 p-4 border border-dashed border-base-300 rounded-xl bg-base-200/50">
                                <x-ui::input
                                    :label="__('internship::ui.company_name')"
                                    wire:model="proposedCompanyName"
                                    required
                                />
                                <x-ui::textarea
                                    :label="__('internship::ui.company_address')"
                                    wire:model="proposedCompanyAddress"
                                    required
                                />
                                <div class="alert alert-info py-2">
                                    <x-ui::icon name="tabler.info-circle" class="size-4" />
                                    <span class="text-xs">{{ __('internship::ui.proposal_notice') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui::input
                            type="date"
                            :label="__('internship::ui.start_date')"
                            wire:model="form.start_date"
                            required
                        />
                        <x-ui::input
                            type="date"
                            :label="__('internship::ui.end_date')"
                            wire:model="form.end_date"
                            required
                        />
                    </div>
                @endif

                <div class="pt-4">
                    <x-ui::button
                        :label="__('internship::ui.submit_registration')"
                        type="submit"
                        variant="primary"
                        class="w-full"
                        spinner="submit"
                        :disabled="!$form->internship_id"
                    />
                </div>
            </div>
        </x-ui::form>
    </x-ui::card>
</div>

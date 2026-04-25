<div class="max-w-2xl mx-auto space-y-8 py-8">
    <x-ui::header
        :title="__('internship::ui.registration_title')"
        :subtitle="__('internship::ui.registration_subtitle')"
    />

    <x-ui::card class="card-enterprise p-8">
        <x-ui::form wire:submit="submit">
            <div class="space-y-8">
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
                    <div class="animate-in fade-in slide-in-from-top-4 duration-500 space-y-8">
                        {{-- Placement Selection --}}
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-black uppercase tracking-widest opacity-40">{{ __('internship::ui.placement') }}</label>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <span class="text-xs font-bold text-base-content/60 group-hover:text-primary transition-colors">{{ __('internship::ui.propose_new_partner') }}</span>
                                    <input type="checkbox" class="toggle toggle-primary toggle-sm" wire:model.live="proposeNewPartner" />
                                </label>
                            </div>

                            @if(!$proposeNewPartner)
                                <x-ui::select
                                    icon="tabler.building"
                                    wire:model="form.placement_id"
                                    :options="$this->availablePlacements"
                                    :placeholder="__('ui::common.select')"
                                    required
                                />
                            @else
                                <div class="space-y-6 p-6 border-2 border-dashed border-base-content/10 rounded-3xl bg-base-200/30 animate-in zoom-in-95 duration-300">
                                    <x-ui::input
                                        :label="__('internship::ui.company_name')"
                                        icon="tabler.building-plus"
                                        wire:model="proposedCompanyName"
                                        required
                                    />
                                    <x-ui::textarea
                                        :label="__('internship::ui.company_address')"
                                        icon="tabler.map-pin"
                                        wire:model="proposedCompanyAddress"
                                        required
                                        rows="2"
                                    />
                                    <div class="p-4 bg-info/10 text-info rounded-2xl flex items-start gap-4">
                                        <x-ui::icon name="tabler.info-circle" class="size-5 mt-0.5" />
                                        <span class="text-xs font-medium leading-relaxed">{{ __('internship::ui.proposal_notice') }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Dates --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-ui::input
                                type="date"
                                :label="__('internship::ui.start_date')"
                                icon="tabler.calendar-play"
                                wire:model="form.start_date"
                                required
                            />
                            <x-ui::input
                                type="date"
                                :label="__('internship::ui.end_date')"
                                icon="tabler.calendar-stop"
                                wire:model="form.end_date"
                                required
                            />
                        </div>
                    </div>
                @endif

                <div class="pt-4">
                    <x-ui::button
                        :label="__('internship::ui.submit_registration')"
                        icon="tabler.rocket"
                        type="submit"
                        variant="primary"
                        class="btn-block btn-lg shadow-xl shadow-primary/20 rounded-2xl"
                        spinner="submit"
                        :disabled="!$form->internship_id"
                    />
                </div>
            </div>
        </x-ui::form>
    </x-ui::card>
</div>

<div>
    <x-ui::card title="{{ __('internship::ui.requirements') }}" subtitle="{{ __('internship::ui.requirements_subtitle') }}">
        <div class="space-y-6">
            @foreach($requirements as $requirement)
                @php
                    $submission = $submissions[$requirement->id] ?? null;
                    $status = $submission?->status;
                @endphp

                <div class="flex flex-col gap-4 border-b pb-6 last:border-0 md:flex-row md:items-center md:justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold">{{ $requirement->name }}</span>
                            @if($requirement->is_mandatory)
                                <x-ui::badge :label="__('internship::ui.mandatory')" class="badge-error badge-xs" />
                            @endif
                            @if($status)
                                <x-ui::badge :label="$status->label()" class="badge-{{ $status->color() }} badge-sm" />
                            @endif
                        </div>
                        <p class="text-sm opacity-70">{{ $requirement->description }}</p>
                    </div>

                    <div class="w-full md:w-1/2">
                        <div class="flex flex-col gap-2">
                            @if($requirement->type->value === 'document')
                                <x-ui::file wire:model="files.{{ $requirement->id }}" label="{{ __('internship::ui.upload_document') }}" accept=".pdf,.doc,.docx,.jpg,.png" />
                                @if($submission && $submission->hasMedia('document'))
                                    <div class="text-xs">
                                        <a href="{{ $submission->getFirstMediaUrl('document') }}" target="_blank" class="link-primary flex items-center gap-1">
                                            <x-ui::icon name="tabler.file-download" class="w-4 h-4" />
                                            {{ __('internship::ui.view_current_document') }}
                                        </a>
                                    </div>
                                @endif
                            @elseif($requirement->type->value === 'skill')
                                <x-ui::select 
                                    wire:model="values.{{ $requirement->id }}" 
                                    label="{{ __('internship::ui.self_rating') }}"
                                    :options="[
                                        ['id' => '1', 'name' => '1 - Beginner'],
                                        ['id' => '2', 'name' => '2 - Basic'],
                                        ['id' => '3', 'name' => '3 - Intermediate'],
                                        ['id' => '4', 'name' => '4 - Advanced'],
                                        ['id' => '5', 'name' => '5 - Expert'],
                                    ]"
                                />
                            @elseif($requirement->type->value === 'condition')
                                <x-ui::checkbox wire:model="values.{{ $requirement->id }}" label="{{ __('internship::ui.i_agree_confirm') }}" />
                            @endif

                            @if(!$status || $status->value === 'rejected' || $status->value === 'draft')
                                <div class="mt-2 flex justify-end">
                                    <x-ui::button 
                                        label="{{ __('internship::ui.submit_requirement') }}" 
                                        class="btn-primary btn-sm" 
                                        wire:click="submit('{{ $requirement->id }}')" 
                                        spinner="submit('{{ $requirement->id }}')"
                                    />
                                </div>
                            @endif

                            @if($submission && $submission->notes)
                                <div class="alert alert-warning mt-2 text-xs">
                                    <x-ui::icon name="tabler.alert-triangle" />
                                    <span>{{ $submission->notes }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($registration->hasClearedAllMandatoryRequirements())
            <div class="mt-6 alert alert-success">
                <x-ui::icon name="tabler.circle-check" />
                <span>{{ __('internship::ui.all_mandatory_requirements_cleared') }}</span>
            </div>
        @endif
    </x-ui::card>
</div>

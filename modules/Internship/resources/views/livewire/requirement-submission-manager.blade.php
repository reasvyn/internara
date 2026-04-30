<div class="space-y-8">
    {{-- Header --}}
    <x-ui::header 
        :title="__('internship::ui.requirements')" 
        :subtitle="__('internship::ui.requirements_subtitle')"
    >
        <x-slot:actions>
            <div class="flex items-center gap-3">
                <div class="flex flex-col items-end">
                    <span class="text-[10px] font-black uppercase tracking-widest opacity-40">{{ __('internship::ui.completion_status') }}</span>
                    <span class="text-sm font-bold {{ $registration->hasClearedAllMandatoryRequirements() ? 'text-success' : 'text-warning' }}">
                        {{ $registration->getRequirementCompletionPercentage() }}% {{ __('internship::ui.complete') }}
                    </span>
                </div>
            </div>
        </x-slot:actions>
    </x-ui::header>

    {{-- Progress Indicator --}}
    <div class="h-2 w-full bg-base-content/5 rounded-full overflow-hidden shadow-inner">
        <div class="h-full bg-success shadow-[0_0_12px_rgba(34,197,94,0.4)] transition-all duration-1000" style="width: {{ $registration->getRequirementCompletionPercentage() }}%"></div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        @foreach($requirements as $requirement)
            @php
                $submission = $submissions[$requirement->id] ?? null;
                $status = $submission?->status;
            @endphp

            <x-ui::card class="!p-0 overflow-hidden border-none shadow-lg hover:shadow-xl transition-all duration-300 group">
                <div class="flex flex-col md:flex-row">
                    {{-- Status Indicator Sidebar --}}
                    <div class="w-2 {{ !$status ? 'bg-base-300' : match($status->value) {
                        'verified' => 'bg-success',
                        'pending' => 'bg-warning',
                        'rejected' => 'bg-error',
                        default => 'bg-info'
                    } }} transition-colors duration-500"></div>

                    <div class="flex-1 p-6 flex flex-col md:flex-row gap-6 items-start md:items-center">
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-3">
                                <h4 class="font-bold text-lg text-base-content/90">{{ $requirement->name }}</h4>
                                @if($requirement->is_mandatory)
                                    <x-ui::badge :value="__('internship::ui.mandatory')" variant="error" class="badge-xs font-black text-[8px] uppercase tracking-widest" />
                                @endif
                                @if($status)
                                    <x-ui::badge 
                                        :value="$status->label()" 
                                        :variant="match($status->value) {
                                            'verified' => 'success',
                                            'pending' => 'warning',
                                            'rejected' => 'error',
                                            default => 'info'
                                        }"
                                        class="badge-sm font-black text-[9px] uppercase tracking-tighter"
                                    />
                                @endif
                            </div>
                            <p class="text-sm opacity-60 leading-relaxed max-w-2xl">{{ $requirement->description }}</p>
                        </div>

                        <div class="w-full md:w-80 space-y-4">
                            {{-- Input Types --}}
                            <div class="bg-base-200/50 p-4 rounded-2xl border border-base-content/5">
                                @if($requirement->type->value === 'document')
                                    @php
                                        $media = $submission?->getFirstMedia('document');
                                        $previewUrl = $media?->getUrl();
                                        $previewType = $media?->mime_type;
                                    @endphp
                                    <x-ui::file 
                                        wire:model="files.{{ $requirement->id }}" 
                                        :label="__('internship::ui.upload_document')" 
                                        accept=".pdf,.doc,.docx,.jpg,.png" 
                                        :preview="$previewUrl"
                                        :preview-type="$previewType"
                                        class="file-input-sm shadow-sm"
                                    />
                                @elseif($requirement->type->value === 'skill')
                                    <x-ui::select 
                                        wire:model="values.{{ $requirement->id }}" 
                                        :label="__('internship::ui.self_rating')"
                                        icon="tabler.star"
                                        :options="[
                                            ['id' => '1', 'name' => '1 - Beginner'],
                                            ['id' => '2', 'name' => '2 - Basic'],
                                            ['id' => '3', 'name' => '3 - Intermediate'],
                                            ['id' => '4', 'name' => '4 - Advanced'],
                                            ['id' => '5', 'name' => '5 - Expert'],
                                        ]"
                                        class="select-sm"
                                    />
                                @elseif($requirement->type->value === 'condition')
                                    <div class="py-2">
                                        <x-ui::checkbox 
                                            wire:model="values.{{ $requirement->id }}" 
                                            :label="__('internship::ui.i_agree_confirm')" 
                                            class="checkbox-primary checkbox-sm"
                                        />
                                    </div>
                                @endif
                            </div>

                            @if(!$status || $status->value === 'rejected' || $status->value === 'draft')
                                <x-ui::button 
                                    :label="__('internship::ui.submit_requirement')" 
                                    icon="tabler.upload"
                                    class="btn-primary btn-block shadow-md group-hover:scale-[1.02] transition-transform" 
                                    wire:click="submit('{{ $requirement->id }}')" 
                                    spinner="submit('{{ $requirement->id }}')"
                                />
                            @endif

                            @if($submission && $submission->notes)
                                <div class="p-3 bg-error/10 text-error rounded-xl flex items-start gap-3 border border-error/20">
                                    <x-ui::icon name="tabler.alert-triangle" class="size-4 mt-1" />
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black uppercase tracking-widest opacity-60">{{ __('internship::ui.reviewer_notes') }}</span>
                                        <span class="text-xs font-medium">{{ $submission->notes }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </x-ui::card>
        @endforeach
    </div>

    @if($registration->hasClearedAllMandatoryRequirements())
        <div class="bg-success text-success-content p-6 rounded-3xl shadow-xl shadow-success/20 flex items-center gap-6 animate-in zoom-in duration-500">
            <div class="size-16 rounded-2xl bg-white/20 flex items-center justify-center">
                <x-ui::icon name="tabler.circle-check" class="size-10" />
            </div>
            <div class="flex flex-col">
                <h3 class="text-xl font-bold uppercase tracking-tight">{{ __('internship::ui.ready_for_placement') }}</h3>
                <p class="opacity-80 font-medium">{{ __('internship::ui.all_mandatory_requirements_cleared') }}</p>
            </div>
        </div>
    @endif
</div>

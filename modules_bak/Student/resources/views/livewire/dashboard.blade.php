<div>
    <x-ui::header 
        :title="__('student::ui.dashboard.title')" 
        :subtitle="__('student::ui.dashboard.welcome', ['name' => auth()->user()->name])" 
        :context="'admin::ui.menu.dashboard'"
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if($this->registration)
                
                @if(!$this->registration->hasClearedAllMandatoryRequirements())
                    <x-ui::alert type="warning" shadow :title="__('student::ui.dashboard.requirements_incomplete.title')">
                        {{ __('student::ui.dashboard.requirements_incomplete.description') }}
                    </x-ui::alert>
                    
                    <x-ui::slot-render name="student.dashboard.requirements" />

                
                @elseif($this->registration->placement)
                    <x-ui::card :title="__('student::ui.dashboard.my_program')" shadow separator>
                        <div class="flex flex-col gap-6">
                            <div class="flex items-center gap-4">
                                <div class="bg-primary/10 p-3 rounded-xl">
                                    <x-ui::icon name="tabler.briefcase" class="size-8 text-primary" />
                                </div>
                                <div>
                                    <div class="font-bold text-lg">{{ $this->registration->placement->company_name }}</div>
                                    <div class="text-sm opacity-70">{{ $this->registration->internship->name }}</div>
                                </div>
                            </div>

                            <hr class="opacity-50">

                            @if($this->scoreCard['final_grade'])
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-base-200 p-4 rounded-lg text-center">
                                        <div class="text-xs uppercase opacity-70">{{ __('student::ui.dashboard.score.final_grade') }}</div>
                                        <div class="text-3xl font-black text-primary">{{ number_format($this->scoreCard['final_grade'], 2) }}</div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <x-ui::button 
                                            :label="__('student::ui.dashboard.score.download_certificate')" 
                                            icon="tabler.certificate" 
                                            variant="primary" 
                                            class="btn-sm" 
                                            link="{{ route('assessment.certificate', $this->registration->id) }}" 
                                        />
                                        <x-ui::button 
                                            :label="__('student::ui.dashboard.score.download_transcript')" 
                                            icon="tabler.file-description" 
                                            variant="secondary" 
                                            class="btn-sm" 
                                            link="{{ route('assessment.transcript', $this->registration->id) }}" 
                                        />
                                    </div>
                                </div>
                            @else
                                <x-ui::alert type="info">
                                    {{ __('student::ui.dashboard.score.processing') }}
                                </x-ui::alert>
                            @endif
                        </div>
                    </x-ui::card>

                    <x-ui::slot-render name="student.dashboard.active-content" />
                @else
                    {{-- Requirements Cleared, but Waiting for Placement --}}
                    <x-ui::card :title="__('student::ui.dashboard.waiting_placement.title')" class="bg-base-100 border-l-4 border-info">
                        <div class="flex items-center gap-4">
                            <div class="bg-info/10 p-3 rounded-full">
                                <x-ui::icon name="tabler.clock" class="size-6 text-info" />
                            </div>
                            <div>
                                <p>{{ __('student::ui.dashboard.waiting_placement.description') }}</p>
                                <p class="text-sm opacity-70">{{ __('student::ui.dashboard.waiting_placement.extra') }}</p>
                            </div>
                        </div>
                    </x-ui::card>
                @endif
            @else
                <x-ui::alert type="warning">
                    {{ __('student::ui.dashboard.not_registered') }}
                </x-ui::alert>
            @endif
        </div>

        <div class="space-y-6">
            @if($this->registration && $this->registration->placement)
                <x-ui::slot-render name="student.dashboard.sidebar" />
                
                <x-ui::card :title="__('student::ui.dashboard.quick_links')" shadow separator>
                    <div class="grid grid-cols-1 gap-2">
                        <x-ui::slot-render name="student.dashboard.quick-actions" />
                    </div>
                </x-ui::card>
            @endif
        </div>
    </div>
</div>

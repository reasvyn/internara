<div class="p-8">
    <x-mary-header
        :title="__('journals.supervision.logs_title')"
        :subtitle="__('journals.supervision.logs_subtitle')"
        separator
        progress-indicator
    />

    @if (! $registration)
        <div class="alert alert-warning">
            <x-mary-icon name="o-exclamation-triangle" class="h-5 w-5" />
            {{ __('journals.supervision.no_active_registration') }}
        </div>
    @else
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
            <div class="space-y-6 md:col-span-2">
                <x-mary-card shadow class="bg-base-100 border-base-200 border">
                    @php
                        $headers = [
                            ['key' => 'date', 'label' => __('journals.date')],
                            ['key' => 'type', 'label' => __('journals.supervision.type')],
                            ['key' => 'topic', 'label' => __('journals.topic')],
                            ['key' => 'is_verified', 'label' => __('journals.status')],
                        ];
                    @endphp

                    <x-mary-table :headers="$headers" :rows="$logs" with-pagination>
                        @scope('cell_date', $log)
                            {{ $log->date->format('d M Y') }}
                        @endscope

                        @scope('cell_type', $log)
                            <x-mary-badge
                                :value="ucfirst($log->type)"
                                :class="$log->type === 'guidance' ? 'badge-primary' : 'badge-secondary'"
                            />
                        @endscope

                        @scope('cell_is_verified', $log)
                            @if ($log->is_verified)
                                <x-mary-badge :value="__('journals.verified')" class="badge-success" />
                            @else
                                <x-mary-badge :value="__('journals.pending')" class="badge-neutral" />
                            @endif
                        @endscope
                    </x-mary-table>
                </x-mary-card>
            </div>

            <div class="space-y-6">
                <x-mary-card :title="__('journals.supervision.assigned_supervisors')" shadow class="bg-base-200/50">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary/10 rounded-lg p-2">
                                <x-mary-icon name="o-user-group" class="text-primary h-5 w-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase opacity-50">
                                    {{ __('journals.supervision.teacher_supervisor') }}
                                </div>
                                <div class="font-medium">
                                    {{ $registration->teacher?->name ?? __('journals.supervision.not_assigned') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="bg-secondary/10 rounded-lg p-2">
                                <x-mary-icon name="o-briefcase" class="text-secondary h-5 w-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase opacity-50">
                                    {{ __('journals.supervision.industry_mentor') }}
                                </div>
                                <div class="font-medium">
                                    {{ $registration->mentor?->name ?? __('journals.supervision.not_assigned') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </x-mary-card>
            </div>
        </div>
    @endif
</div>

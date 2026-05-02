<div>
    <x-ui::table :rows="$registrations" :headers="[
        ['key' => 'student.name', 'label' => __('guidance::ui.student')],
        ['key' => 'status', 'label' => __('guidance::ui.status')],
    ]" with-pagination>
        @scope('cell_student.name', $reg)
            <div class="font-bold">{{ $reg->student->name }}</div>
            <div class="text-xs opacity-60">{{ $reg->student->username }}</div>
        @endscope

        @scope('cell_status', $reg)
            @php
                $isComplete = app(\Modules\Guidance\Services\Contracts\HandbookService::class)->hasCompletedMandatory($reg->student_id);
            @endphp

            @if($isComplete)
                <x-ui::badge label="{{ __('guidance::ui.complete') }}" class="badge-success badge-sm" />
            @else
                <x-ui::badge label="{{ __('guidance::ui.incomplete') }}" class="badge-warning badge-sm" />
            @endif
        @endscope
    </x-ui::table>
</div>

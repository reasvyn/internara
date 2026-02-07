<?php

declare(strict_types=1);

namespace Modules\Schedule\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Schedule\Services\Contracts\ScheduleService;
use Modules\Setting\Services\Contracts\SettingService;

/**
 * Class ScheduleForm
 *
 * Handles the creation and modification of schedule records.
 */
class ScheduleForm extends Component
{
    /**
     * The schedule ID being edited, if any.
     */
    public ?string $scheduleId = null;

    /**
     * Form data.
     */
    public array $data = [
        'title' => '',
        'description' => '',
        'start_at' => '',
        'end_at' => '',
        'type' => 'event',
        'location' => '',
        'academic_year' => '',
    ];

    /**
     * Initialize the component.
     */
    public function mount(
        ?string $scheduleId,
        ScheduleService $service,
        SettingService $settingService,
    ): void {
        $this->scheduleId = $scheduleId;

        // Set default academic year
        $this->data['academic_year'] = $settingService->getValue('active_academic_year', '');

        if ($this->scheduleId) {
            $schedule = $service->find($this->scheduleId);
            if ($schedule) {
                $this->data = [
                    'title' => $schedule->title,
                    'description' => $schedule->description ?? '',
                    'start_at' => $schedule->start_at->format('Y-m-d\TH:i'),
                    'end_at' => $schedule->end_at ? $schedule->end_at->format('Y-m-d\TH:i') : '',
                    'type' => $schedule->type,
                    'location' => $schedule->location ?? '',
                    'academic_year' => $schedule->academic_year,
                ];
            }
        }
    }

    /**
     * Validation rules.
     */
    protected function rules(): array
    {
        return [
            'data.title' => 'required|string|max:255',
            'data.description' => 'nullable|string',
            'data.start_at' => 'required|date',
            'data.end_at' => 'nullable|date|after_or_equal:data.start_at',
            'data.type' => 'required|string|in:event,deadline,briefing',
            'data.location' => 'nullable|string|max:255',
            'data.academic_year' => 'required|string',
        ];
    }

    /**
     * Save the schedule.
     */
    public function save(ScheduleService $service): void
    {
        $this->validate();

        if ($this->scheduleId) {
            $service->update($this->scheduleId, $this->data);
        } else {
            $service->create($this->data);
        }

        $this->dispatch('scheduleSaved');
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        $types = [
            ['id' => 'event', 'name' => __('schedule::ui.type_event')],
            ['id' => 'deadline', 'name' => __('schedule::ui.type_deadline')],
            ['id' => 'briefing', 'name' => __('schedule::ui.type_briefing')],
        ];

        return view('schedule::livewire.schedule-form', [
            'types' => $types,
        ]);
    }
}

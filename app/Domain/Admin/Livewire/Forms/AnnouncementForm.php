<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire\Forms;

use Livewire\Form;

class AnnouncementForm extends Form
{
    public string $title = '';

    public string $message = '';

    public string $type = 'info';

    public string $status = 'draft';

    public ?string $scheduled_at = null;

    public ?string $link = null;

    /** @var string[] */
    public array $target_roles = [];

    public bool $sendToAll = true;

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'type' => 'required|in:info,success,warning,error',
            'status' => 'required|in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date|after_or_equal:now|required_if:status,scheduled',
            'link' => 'nullable|string|max:500',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'string|exists:roles,name',
        ];
    }

    public function toPayload(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'status' => $this->status,
            'scheduled_at' => $this->status === 'scheduled' ? $this->scheduled_at : null,
            'link' => $this->link ?: null,
            'target_roles' => $this->sendToAll ? null : $this->target_roles,
        ];
    }
}

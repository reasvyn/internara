<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Models\Briefing;
use Illuminate\Support\Facades\Validator;

class CreateBriefingAction extends BaseAction
{
    public function execute(array $data): Briefing
    {
        $validated = Validator::validate($data, [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'is_mandatory' => 'boolean',
            'internship_id' => 'required|exists:internships,id',
            'created_by' => 'required|exists:users,id',
        ]);

        return $this->transaction(function () use ($validated) {
            $briefing = Briefing::create($validated);

            $this->log('briefing_created', $briefing, ['title' => $briefing->title, 'internship_id' => $briefing->internship_id]);

            return $briefing;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Briefing;

use App\Actions\Core\LogAuditAction;
use App\Models\Briefing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateBriefingAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

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

        return DB::transaction(function () use ($validated) {
            $briefing = Briefing::create($validated);

            $this->logAudit->execute(
                action: 'briefing_created',
                subjectType: Briefing::class,
                subjectId: $briefing->id,
                payload: ['title' => $briefing->title, 'internship_id' => $briefing->internship_id],
                module: 'Briefing',
            );

            return $briefing;
        });
    }
}

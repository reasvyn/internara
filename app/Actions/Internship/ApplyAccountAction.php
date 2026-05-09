<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\AccountApplication;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApplyAccountAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    public function execute(array $data): AccountApplication
    {
        $existing = AccountApplication::where('email', $data['email'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existing) {
            throw new RuntimeException('An application with this email already exists.');
        }

        return DB::transaction(function () use ($data) {
            /** @var AccountApplication $application */
            $application = AccountApplication::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'national_identifier' => $data['national_identifier'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'school_id' => $data['school_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'class_name' => $data['class_name'] ?? null,
                'entry_year' => $data['entry_year'] ?? null,
                'internship_id' => $data['internship_id'],
                'placement_id' => $data['placement_id'] ?? null,
                'academic_year' => $data['academic_year'] ?? null,
                'proposed_company_name' => $data['proposed_company_name'] ?? null,
                'proposed_company_address' => $data['proposed_company_address'] ?? null,
            ]);

            $this->logAuditAction->execute(
                action: 'account_applied',
                subjectType: AccountApplication::class,
                subjectId: $application->id,
                payload: $data,
                module: 'Internship',
            );

            return $application;
        });
    }
}

<?php

declare(strict_types=1);

namespace Modules\Support\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\Student\Services\Contracts\StudentService;
use Modules\Support\Services\Contracts\OnboardingService as Contract;
use Modules\Teacher\Services\Contracts\TeacherService;
use Modules\User\Services\Contracts\UserService;

/**
 * Class OnboardingService
 *
 * Implementation for batch onboarding stakeholders via CSV.
 */
class OnboardingService implements Contract
{
    public function __construct(
        protected UserService $userService,
        protected ProfileService $profileService,
        protected StudentService $studentService,
        protected TeacherService $teacherService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function importFromCsv(string $filePath, string $type): array
    {
        $results = [
            'success' => 0,
            'failure' => 0,
            'errors' => [],
        ];

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            $results['errors'][] = 'File not found or not readable.';

            return $results;
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            $results['errors'][] = 'Empty CSV file.';

            return $results;
        }

        // Normalize header keys
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $rowCount = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            if (count($header) !== count($row)) {
                $results['failure']++;
                $results['errors'][] = "Row {$rowCount}: Column count mismatch.";

                continue;
            }

            $data = array_combine($header, $row);

            try {
                DB::transaction(function () use ($data, $type) {
                    $this->processRow($data, $type);
                });
                $results['success']++;
            } catch (\Throwable $e) {
                $results['failure']++;
                $results['errors'][] = "Row {$rowCount}: ".$e->getMessage();
            }
        }

        fclose($handle);

        return $results;
    }

    public function getTemplate(string $type): string
    {
        $columns = ['name', 'email', 'username', 'password', 'phone', 'address'];

        if ($type === 'student') {
            $columns[] = 'nisn';
        } elseif ($type === 'teacher') {
            $columns[] = 'nip';
        }

        return implode(',', $columns) . "\n";
    }

    /**
     * Process a single CSV row.
     */
    protected function processRow(array $data, string $type): void
    {
        // 1. Prepare User Data
        $userData = [
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'username' => $data['username'] ?? null,
            'password' => $data['password'] ?? Str::random(12),
            'roles' => [$type],
            'profile' => [
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'department_id' => $data['department_id'] ?? null,
            ],
        ];

        if (empty($userData['name']) || empty($userData['email'])) {
            throw new \InvalidArgumentException('Name and Email are required.');
        }

        if (! filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format.');
        }

        // 2. Create User (this also initializes profile and profileable via syncProfileable)
        $user = $this->userService->create($userData);

        // 3. Update Profileable specific data (nisn/nip)
        $profile = $user->profile;
        if ($profile && $profile->profileable) {
            if ($type === 'student' && ! empty($data['nisn'])) {
                $profile->profileable->update(['nisn' => $data['nisn']]);
            } elseif ($type === 'teacher' && ! empty($data['nip'])) {
                $profile->profileable->update(['nip' => $data['nip']]);
            }
        }
    }
}

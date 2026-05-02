<?php

declare(strict_types=1);

namespace Modules\Report\Services\Contracts;

interface ReportService
{
    public function generateStudentReport(string $studentId): array;

    public function generateAttendanceReport(array $filters): array;

    public function generateGradeReport(string $classId): array;

    public function exportToPdf(array $data, string $template): string;
}

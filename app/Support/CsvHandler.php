<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvHandler
{
    public function export(Collection $items, array $headers, callable $rowMapper, string $filename = 'export.csv'): StreamedResponse
    {
        $callback = function () use ($items, $headers, $rowMapper) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($items as $item) {
                fputcsv($handle, $rowMapper($item));
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function downloadTemplate(array $headers, array $exampleRow, string $filename = 'template.csv'): StreamedResponse
    {
        $callback = function () use ($headers, $exampleRow) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, $exampleRow);
            fclose($handle);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function import(string $filePath, callable $rowProcessor): array
    {
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);

        if ($header === false || strtolower(trim($header[0] ?? '')) !== 'name') {
            fclose($handle);

            return ['created' => 0, 'skipped' => 0, 'invalid' => true];
        }

        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $result = $rowProcessor($row);

            if ($result === null) {
                continue;
            }

            if ($result === 'skipped') {
                $skipped++;

                continue;
            }

            $created++;
        }

        fclose($handle);

        return ['created' => $created, 'skipped' => $skipped, 'invalid' => false];
    }
}

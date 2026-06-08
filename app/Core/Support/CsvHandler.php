<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Enums\CsvRowResult;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvHandler
{
    public function export(
        Collection $items,
        array $headers,
        callable $rowMapper,
        string $filename = 'export.csv',
    ): StreamedResponse {
        $callback = function () use ($items, $headers, $rowMapper) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers, escape: '');

            foreach ($items as $item) {
                fputcsv($handle, $rowMapper($item), escape: '');
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function downloadTemplate(
        array $headers,
        array $exampleRow,
        string $filename = 'template.csv',
    ): StreamedResponse {
        $callback = function () use ($headers, $exampleRow) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers, escape: '');
            fputcsv($handle, $exampleRow, escape: '');
            fclose($handle);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function import(
        string $filePath,
        callable $rowProcessor,
        ?array $expectedHeaders = null,
    ): array {
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, escape: '');

        if ($expectedHeaders !== null) {
            foreach ($expectedHeaders as $i => $expected) {
                $actual = trim($header[$i] ?? '');
                if (strtolower($actual) !== strtolower($expected)) {
                    fclose($handle);

                    return ['created' => 0, 'skipped' => 0, 'invalid' => true];
                }
            }
        }

        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, escape: '')) !== false) {
            $result = $rowProcessor($row);

            if ($result === null) {
                continue;
            }

            if ($result === CsvRowResult::SKIPPED) {
                $skipped++;

                continue;
            }

            $created++;
        }

        fclose($handle);

        return ['created' => $created, 'skipped' => $skipped, 'invalid' => false];
    }
}

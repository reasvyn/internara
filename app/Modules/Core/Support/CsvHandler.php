<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use App\Modules\Core\Enums\CsvRowResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

final class CsvHandler
{
    private const CONTENT_TYPE_CSV = 'text/csv';
    private const DEFAULT_CHUNK_SIZE = 500;

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

        return new StreamedResponse($callback, Response::HTTP_OK, $this->csvHeaders($filename));
    }

    public function exportChunked(
        Builder $query,
        array $headers,
        callable $rowMapper,
        string $filename = 'export.csv',
        int $chunkSize = self::DEFAULT_CHUNK_SIZE,
    ): StreamedResponse {
        $callback = function () use ($query, $headers, $rowMapper, $chunkSize) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers, escape: '');

            $query->chunkById($chunkSize, function (Collection $models) use ($handle, $rowMapper) {
                foreach ($models as $model) {
                    fputcsv($handle, $rowMapper($model), escape: '');
                }
            });

            fclose($handle);
        };

        return new StreamedResponse($callback, Response::HTTP_OK, $this->csvHeaders($filename));
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

        return new StreamedResponse($callback, Response::HTTP_OK, $this->csvHeaders($filename));
    }

    public function import(
        string $filePath,
        callable $rowProcessor,
        ?array $expectedHeaders = null,
    ): array {
        $handle = fopen($filePath, 'r');

        try {
            $header = fgetcsv($handle, escape: '');

            if ($expectedHeaders !== null) {
                foreach ($expectedHeaders as $i => $expected) {
                    $actual = trim($header[$i] ?? '');
                    if (strtolower($actual) !== strtolower($expected)) {
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

            return ['created' => $created, 'skipped' => $skipped, 'invalid' => false];
        } finally {
            fclose($handle);
        }
    }

    private function csvHeaders(string $filename): array
    {
        return [
            'Content-Type' => self::CONTENT_TYPE_CSV,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
    }
}

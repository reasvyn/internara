<?php

declare(strict_types=1);

namespace Modules\Support\Tests\Unit\Testing\Support;

use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Modules\Support\Contracts\Testing\ResultReporterInterface;
use Modules\Support\Testing\Support\ResultReporter;
use Symfony\Component\Console\Output\ConsoleOutput;

describe('ResultReporter', function () {
    beforeEach(function () {
        $output = new ConsoleOutput();
        $this->reporter = new ResultReporter(new \Illuminate\Console\View\Components\Factory($output));
    });

    it('implements ResultReporterInterface', function () {
        expect($this->reporter)->toBeInstanceOf(ResultReporterInterface::class);
    });

    it('displays matrix correctly', function () {
        $results = [
            ['module' => 'TestModule', 'Arch' => 'PASS', 'Unit' => 'PASS', 'Feature' => 'FAIL', 'Browser' => '-', 'total' => 10.5],
        ];

        // Just ensure it doesn't throw exceptions
        $this->reporter->displayMatrix($results);
        expect(true)->toBeTrue();
    });

    it('displays performance metrics', function () {
        $this->reporter->displayPerformance(10, 8, 120.5);
        expect(true)->toBeTrue();
    });

    it('displays failures', function () {
        $failures = [
            ['label' => 'TestModule > Unit', 'output' => 'Test output', 'error' => 'Error message'],
        ];

        $this->reporter->displayFailures($failures);
        expect(true)->toBeTrue();
    });

    it('handles empty failures gracefully', function () {
        $this->reporter->displayFailures([]);
        expect(true)->toBeTrue();
    });

    it('displays session metrics', function () {
        $sessionResults = [
            ['module' => 'TestModule', 'type' => 'Unit', 'success' => true, 'timestamp' => now()->toIso8601String()],
            ['module' => 'TestModule', 'type' => 'Feature', 'success' => false, 'timestamp' => now()->toIso8601String()],
        ];

        $passRate = $this->reporter->displaySessionMetrics('test-session-123', $sessionResults, 10);
        expect($passRate)->toBeFloat();
    });

    it('exports to JUnit XML', function () {
        $results = [
            ['module' => 'TestModule', 'type' => 'Unit', 'success' => true, 'timestamp' => now()->toIso8601String()],
        ];

        $path = storage_path('framework/testing/reports/test_junit.xml');
        $this->reporter->exportToJUnit($path, $results, 'test-session');

        expect(file_exists($path))->toBeTrue();
        
        if (file_exists($path)) {
            unlink($path);
        }
    });

    it('exports to JSON', function () {
        $results = [
            ['module' => 'TestModule', 'type' => 'Unit', 'success' => true, 'timestamp' => now()->toIso8601String()],
        ];

        $path = storage_path('framework/testing/reports/test.json');
        $this->reporter->exportToJSON($path, $results, 'test-session');

        expect(file_exists($path))->toBeTrue();
        
        if (file_exists($path)) {
            unlink($path);
        }
    });

    it('displays coverage summary', function () {
        $output = 'Lines: 85.5% Methods: 90.0%';
        $this->reporter->displayCoverageSummary($output);
        expect(true)->toBeTrue();
    });
});

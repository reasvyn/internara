<?php

declare(strict_types=1);

namespace Modules\Support\Tests\Unit\Testing\Support;

use Modules\Support\Contracts\Testing\TargetDiscoveryInterface;
use Modules\Support\Testing\Support\TargetDiscovery;

describe('TargetDiscovery', function () {
    beforeEach(function () {
        $this->discovery = new TargetDiscovery;
    });

    it('implements TargetDiscoveryInterface', function () {
        expect($this->discovery)->toBeInstanceOf(TargetDiscoveryInterface::class);
    });

    it('discovers system tests', function () {
        $missing = [];
        $targets = $this->discovery->discover([], false, $missing);

        // Should find at least the System target
        $systemTargets = array_filter($targets, fn ($t) => $t['label'] === 'System');
        expect($systemTargets)->not->toBeEmpty();
    });

    it('discovers module tests', function () {
        $missing = [];
        $targets = $this->discovery->discover([], false, $missing);

        // Should find enabled modules from modules_statuses.json
        expect(count($targets))->toBeGreaterThan(1); // System + at least one module
    });

    it('filters by requested modules', function () {
        $missing = [];
        $targets = $this->discovery->discover(['Shared'], false, $missing);

        $labels = array_column($targets, 'label');
        expect($labels)->toContain('Shared');
    });

    it('reports missing modules', function () {
        $missing = [];
        $this->discovery->discover(['NonExistentModule'], false, $missing);

        expect($missing)->toContain('nonexistentmodule');
    });

    it('checks if module is testable', function () {
        expect($this->discovery->isTestable('Shared'))->toBeTrue();
        expect($this->discovery->isTestable('NonExistent'))->toBeFalse();
    });

    it('gets enabled modules', function () {
        $modules = $this->discovery->getEnabledModules();

        expect($modules)->toBeArray();
        expect($modules['Shared'] ?? false)->toBeTrue();
    });

    it('detects dirty modules', function () {
        $dirty = $this->discovery->detectDirtyModules();

        expect($dirty)->toBeArray();
    });
});

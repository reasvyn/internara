<?php

declare(strict_types=1);

namespace Tests\Unit\Setup;

use App\Domain\Setup\Services\SetupRequirementRegistry;

/**
 * S3 - Scalable: Unit tests for SetupRequirementRegistry.
 * Verifies that requirements can be registered and retrieved correctly.
 */
test('it has default requirements', function () {
    $registry = new SetupRequirementRegistry;

    expect($registry->getRequiredExtensions())->not->toBeEmpty();
    expect($registry->getRecommendedExtensions())->not->toBeEmpty();
    expect($registry->getWritableDirs())->not->toBeEmpty();
});

test('it can register new required extension', function () {
    $registry = new SetupRequirementRegistry;
    $registry->requireExtension('custom_extension');

    expect($registry->getRequiredExtensions())->toContain('custom_extension');
});

test('it can register new recommended extension', function () {
    $registry = new SetupRequirementRegistry;
    $registry->recommendExtension('another_extension');

    expect($registry->getRecommendedExtensions())->toContain('another_extension');
});

test('it can register new writable directory', function () {
    $registry = new SetupRequirementRegistry;
    $registry->requireWritableDir('custom/path');

    expect($registry->getWritableDirs())->toContain('custom/path');
});

test('it does not duplicate requirements', function () {
    $registry = new SetupRequirementRegistry;
    $count = count($registry->getRequiredExtensions());

    $registry->requireExtension($registry->getRequiredExtensions()[0]);

    expect($registry->getRequiredExtensions())->toHaveCount($count);
});

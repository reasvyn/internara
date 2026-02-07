<?php

declare(strict_types=1);

test('it renders primary buttons with monochrome styling', function () {
    $page = visit('/');

    // Primary button should use btn-primary (Black/White)
    $page->assertVisible('.btn-primary')->assertMissing('.btn-accent.btn-primary');

    // Verify background color is black in light mode
    $bgColor = $page->script(
        "window.getComputedStyle(document.querySelector('.btn-primary')).backgroundColor",
    );
    expect($bgColor)->toBe('rgb(0, 0, 0)');
});

test('it renders secondary and tertiary buttons correctly', function () {
    $page = visit('/');

    // Check for outline/secondary button if present (e.g. Register button when logged out)
    if ($page->isPresent('.btn-outline')) {
        $page->assertVisible('.btn-outline');
    }

    // Check for ghost/tertiary button
    if ($page->isPresent('.btn-ghost')) {
        $page->assertVisible('.btn-ghost');
    }
});

test('it enforces minimum touch targets for accessibility', function () {
    $page = visit('/');

    // Non-metadata buttons must be at least 44px (2.75rem)
    $height = $page->script(
        "document.querySelector('.btn-primary').getBoundingClientRect().height",
    );
    expect((float) $height)->toBeGreaterThanOrEqual(44.0);
});

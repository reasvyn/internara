<?php

declare(strict_types=1);

test('it renders the base layout with correct metadata and fonts', function () {
    $page = visit('/');

    // Verify Meta Tags
    $page
        ->assertPresent('meta[charset="utf-8"]')
        ->assertPresent('meta[name="viewport"]')
        ->assertPresent('meta[name="csrf-token"]');

    // Verify Typography (Instrument Sans)
    $fontFamily = $page->script('window.getComputedStyle(document.body).fontFamily');
    expect($fontFamily)->toContain('Instrument Sans');

    // Verify Skip to Content link for A11y
    $page
        ->assertPresent('a[href="#main-content"]')
        ->assertAttribute(
            'a[href="#main-content"]',
            'class',
            'sr-only focus:not-sr-only focus:absolute focus:z-50 focus:p-4 focus:bg-base-100 focus:text-primary',
        );
});

test('it initializes AOS for cinematic motion', function () {
    $page = visit('/');

    // Check if AOS has initialized by looking for the aos-init class
    $page->assertPresent('.aos-init');
});

test('it renders favicon from the internara namespace', function () {
    $page = visit('/');

    $page->assertSourceHas('<link rel="icon" href="/internara/favicon.ico"');
    $page->assertSourceHas('<link rel="apple-touch-icon" href="/internara/apple-touch-icon.png"');
});

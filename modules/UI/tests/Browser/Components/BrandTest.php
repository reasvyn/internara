<?php

declare(strict_types=1);

test('it renders the dynamic brand logo and name from settings', function () {
    $page = visit('/');

    $brandName = setting('brand_name', setting('app_name'));
    $logoPath = setting('brand_logo', '/internara/logo.png');

    // Verify Brand Name visibility
    $page->assertSee($brandName);

    // Verify Brand Logo rendering and attributes
    $page
        ->assertPresent("img[src='{$logoPath}']")
        ->assertAttribute("img[src='{$logoPath}']", 'alt', $brandName);
});

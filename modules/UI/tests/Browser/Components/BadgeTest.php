<?php

declare(strict_types=1);

test('it renders badges with correct priority colors', function () {
    $page = visit('/');

    // If there's a badge on the page, verify it uses primary color scale
    if ($page->isPresent('.badge')) {
        $page->assertVisible('.badge-primary')->assertMissing('.badge-accent.badge-primary');
    }
});

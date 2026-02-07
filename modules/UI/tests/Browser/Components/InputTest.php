<?php

declare(strict_types=1);

test('it uses emerald strictly as an accent for form inputs', function () {
    // Visit a page with forms, e.g., login or register
    $page = visit('/login');

    // Check if input has the focus:border-accent class in source
    $page->assertSourceHas('focus:border-accent');

    // If checkboxes or radios exist, they should use the accent variant
    $page->navigate('/register');

    if ($page->isPresent('.checkbox')) {
        $page->assertPresent('.checkbox-accent');
    }

    if ($page->isPresent('.radio')) {
        $page->assertPresent('.radio-accent');
    }
});

<?php

declare(strict_types=1);

test('it renders native toast notifications via event dispatch', function () {
    $page = visit('/');

    // Dispatch custom notify event
    $page->script(
        "window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Integrity Verified', type: 'success' } }))",
    );

    // Verify toast behavior
    $page->waitFor('.alert-success')->assertSee('Integrity Verified')->assertSee('SUCCESS'); // Title is auto-generated and capitalized

    // Verify cleanup
    $page
        ->click('.btn-circle') // Close button in toast
        ->assertMissing('.alert-success');
});

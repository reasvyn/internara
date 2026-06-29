<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('show renders document pdf', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);

    $document = Document::factory()->create();
    $registration = Registration::factory()->create();

    $response = $this->get(route('sysadmin.documents.render', [$document, $registration]));

    $response->assertStatus(200);
});

<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('can be created with factory', function () {
    $school = School::factory()->create();

    expect($school)->toBeInstanceOf(School::class)
        ->and($school->id)->toBeUuid();
});

it('has logo_url attribute', function () {
    Storage::fake('public');
    $school = School::factory()->create();

    expect($school->logo_url)->toBeNull();

    $file = UploadedFile::fake()->image('logo.png');
    $school->clearMediaCollection(School::COLLECTION_LOGO);
    $school->addMedia($file)->toMediaCollection(School::COLLECTION_LOGO);

    expect($school->fresh()->logo_url)->not->toBeNull();
});

it('has many departments', function () {
    $school = School::factory()->create();
    Department::factory()->count(2)->create(['school_id' => $school->id]);

    expect($school->departments)->toHaveCount(2)
        ->and($school->departments->first())->toBeInstanceOf(Department::class);
});

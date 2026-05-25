<?php

declare(strict_types=1);

use App\Domain\Guidance\Actions\AcknowledgeHandbookAction;
use App\Domain\Guidance\Actions\CreateHandbookAction;
use App\Domain\Guidance\Actions\DeleteHandbookAction;
use App\Domain\Guidance\Actions\UpdateHandbookAction;
use App\Domain\Guidance\Livewire\HandbookIndex;
use App\Domain\Guidance\Livewire\StudentHandbookIndex;
use App\Domain\Guidance\Models\Handbook;
use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::TEACHER->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::SUPERVISOR->value, 'guard_name' => 'web']);
});

describe('CreateHandbookAction', function () {
    it('creates a new handbook', function () {
        $user = User::factory()->create();

        $handbook = app(CreateHandbookAction::class)->execute($user, [
            'title' => 'Internship Guidelines',
            'content' => 'All the rules and regulations.',
            'version' => '2',
            'is_active' => true,
        ]);

        expect($handbook)->toBeInstanceOf(Handbook::class)
            ->and($handbook->title)->toBe('Internship Guidelines')
            ->and($handbook->slug)->toBe('internship-guidelines')
            ->and($handbook->is_active)->toBeTrue()
            ->and($handbook->published_at)->not->toBeNull()
            ->and($handbook->created_by)->toBe($user->id);
    });

    it('creates an inactive draft handbook', function () {
        $user = User::factory()->create();

        $handbook = app(CreateHandbookAction::class)->execute($user, [
            'title' => 'Draft Policy',
            'content' => 'Not yet published.',
            'is_active' => false,
        ]);

        expect($handbook->is_active)->toBeFalse()
            ->and($handbook->published_at)->toBeNull();
    });

    it('creates a handbook with target audience', function () {
        $user = User::factory()->create();

        $handbook = app(CreateHandbookAction::class)->execute($user, [
            'title' => 'Teacher Guide',
            'content' => 'For teachers only.',
            'is_active' => true,
            'target_audience' => 'teacher',
        ]);

        expect($handbook->target_audience)->toBe('teacher');
    });
});

describe('UpdateHandbookAction', function () {
    it('updates handbook fields', function () {
        $handbook = Handbook::factory()->create(['title' => 'Old Title']);

        $result = app(UpdateHandbookAction::class)->execute($handbook, [
            'title' => 'New Title',
            'is_active' => false,
        ]);

        expect($result->title)->toBe('New Title')
            ->and($result->is_active)->toBeFalse();
    });
});

describe('DeleteHandbookAction', function () {
    it('deletes a handbook', function () {
        $handbook = Handbook::factory()->create();

        app(DeleteHandbookAction::class)->execute($handbook);

        expect(Handbook::find($handbook->id))->toBeNull();
    });
});

describe('AcknowledgeHandbookAction', function () {
    it('records user acknowledgement of a handbook', function () {
        $user = User::factory()->create();
        $handbook = Handbook::factory()->create();

        app(AcknowledgeHandbookAction::class)->execute($user, $handbook);

        expect($handbook->acknowledgements()->count())->toBe(1)
            ->and($handbook->acknowledgements()->first()->user_id)->toBe($user->id);
    });
});

describe('HandbookIndex (Admin)', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN->value);
        $this->actingAs($this->admin);
    });

    it('renders the page', function () {
        Livewire::test(HandbookIndex::class)
            ->assertSuccessful();
    });

    it('blocks unauthorized users', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Livewire::test(HandbookIndex::class)
            ->assertForbidden();
    });

    it('creates a handbook via form', function () {
        Livewire::test(HandbookIndex::class)
            ->call('create')
            ->set('form.title', 'Test Handbook')
            ->set('form.content', 'Test content')
            ->set('form.version', '1')
            ->call('store')
            ->assertHasNoErrors();

        expect(Handbook::where('title', 'Test Handbook')->exists())->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(HandbookIndex::class)
            ->call('create')
            ->set('form.title', '')
            ->set('form.content', '')
            ->call('store')
            ->assertHasErrors(['form.title', 'form.content']);
    });

    it('edits a handbook', function () {
        $handbook = Handbook::factory()->create();

        Livewire::test(HandbookIndex::class)
            ->call('edit', $handbook->id)
            ->set('form.title', 'Updated Title')
            ->call('store')
            ->assertHasNoErrors();

        expect($handbook->fresh()->title)->toBe('Updated Title');
    });

    it('deletes a handbook', function () {
        $handbook = Handbook::factory()->create();

        Livewire::test(HandbookIndex::class)
            ->call('delete', $handbook->id)
            ->assertHasNoErrors();

        expect(Handbook::find($handbook->id))->toBeNull();
    });
});

describe('StudentHandbookIndex', function () {
    it('shows handbooks matching student audience', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        Handbook::factory()->create(['title' => 'Student Guide', 'target_audience' => 'student', 'is_active' => true]);
        Handbook::factory()->create(['title' => 'Teacher Guide', 'target_audience' => 'teacher', 'is_active' => true]);

        Livewire::test(StudentHandbookIndex::class)
            ->assertSuccessful();
    });

    it('acknowledges a handbook', function () {
        $student = User::factory()->create()->assignRole(Role::STUDENT->value);
        $this->actingAs($student);

        $handbook = Handbook::factory()->create(['is_active' => true]);

        Livewire::test(StudentHandbookIndex::class)
            ->call('acknowledge', $handbook->id)
            ->assertHasNoErrors();

        expect($handbook->acknowledgements()->count())->toBe(1);
    });
});

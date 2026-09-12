<?php

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function actingAsAdmin(): User
{
    Sanctum::actingAs($admin = User::factory()->admin()->create());

    return $admin;
}

beforeEach(function () {
    Storage::fake('public');
});

describe('listing users', function () {
    it('returns a paginated list', function () {
        actingAsAdmin();
        User::factory()->count(4)->create();

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'role', 'status']], 'meta' => ['current_page', 'total']]);
    });

    it('honours per_page', function () {
        actingAsAdmin();
        User::factory()->count(10)->create();

        $this->getJson('/api/admin/users?per_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 11);
    });

    it('lists the newest users first', function () {
        actingAsAdmin();
        User::factory()->create(['name' => 'Older', 'created_at' => now()->subDay()]);
        User::factory()->create(['name' => 'Newest', 'created_at' => now()->addDay()]);

        expect($this->getJson('/api/admin/users')->assertOk()->json('data.0.name'))->toBe('Newest');
    });

    it('searches by name', function () {
        actingAsAdmin();
        User::factory()->create(['name' => 'Amelia Stone']);
        User::factory()->create(['name' => 'Bartholomew Quill']);

        $this->getJson('/api/admin/users?search=Amelia')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Amelia Stone');
    });

    it('searches by email', function () {
        actingAsAdmin();
        User::factory()->create(['email' => 'findme@example.com']);

        $this->getJson('/api/admin/users?search=findme')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'findme@example.com');
    });

    // The name/email search must stay grouped, or a role filter beside it would
    // be swallowed by the OR.
    it('keeps the search grouped when combined with a role filter', function () {
        actingAsAdmin();
        User::factory()->create(['name' => 'Target', 'email' => 'target@example.com']);
        User::factory()->admin()->create(['name' => 'Other Admin']);

        $this->getJson('/api/admin/users?search=Target&role=admin')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('filters by role', function () {
        actingAsAdmin();
        User::factory()->count(3)->create();

        $this->getJson('/api/admin/users?role=admin')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.role', 'admin');
    });

    it('filters by status', function () {
        actingAsAdmin();
        User::factory()->create(['status' => UserStatus::Suspended]);
        User::factory()->create(['status' => UserStatus::Inactive]);

        $this->getJson('/api/admin/users?status=suspended')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'suspended');
    });

    it('rejects invalid filters', function (string $query, string $field) {
        actingAsAdmin();

        $this->getJson("/api/admin/users?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with([
        'unknown role' => ['role=superuser', 'role'],
        'unknown status' => ['status=banned', 'status'],
        'per_page above the cap' => ['per_page=500', 'per_page'],
        'per_page below one' => ['per_page=0', 'per_page'],
        'non-numeric per_page' => ['per_page=many', 'per_page'],
    ]);

    it('denies a non-admin', function () {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/users')->assertForbidden();
    });

    it('requires authentication', function () {
        $this->getJson('/api/admin/users')->assertUnauthorized();
    });
});

describe('user details', function () {
    it('returns the user with their content stats and books', function () {
        actingAsAdmin();

        $user = User::factory()->create();
        Book::factory()->for($user)->has(Chapter::factory()->count(2), 'chapters')
            ->create(['created_at' => now()->subDay()]);
        Book::factory()->for($user)->has(Chapter::factory()->count(3), 'chapters')
            ->create(['created_at' => now()]);

        $this->getJson("/api/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('stats.books_total', 2)
            ->assertJsonPath('stats.chapters_total', 5)
            ->assertJsonCount(2, 'books')
            // Newest book first.
            ->assertJsonPath('books.0.chapters_count', 3);
    });

    it("does not count another user's content", function () {
        actingAsAdmin();

        $user = User::factory()->create();
        Book::factory()->has(Chapter::factory()->count(4), 'chapters')->create();

        $this->getJson("/api/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('stats.books_total', 0)
            ->assertJsonPath('stats.chapters_total', 0);
    });

    it('404s for an unknown user', function () {
        actingAsAdmin();

        $this->getJson('/api/admin/users/999999')->assertNotFound();
    });

    it('denies a non-admin', function () {
        $target = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/admin/users/{$target->id}")->assertForbidden();
    });
});

describe('updating a user', function () {
    it('applies only the fields that were sent', function () {
        actingAsAdmin();
        $user = User::factory()->create(['name' => 'Original', 'email' => 'original@example.com']);

        $this->patchJson("/api/admin/users/{$user->id}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');

        expect($user->refresh()->name)->toBe('Renamed')
            ->and($user->email)->toBe('original@example.com');
    });

    it('changes the role of another user', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        $this->patchJson("/api/admin/users/{$user->id}", ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');

        expect($user->refresh()->role)->toBe(Role::Admin);
    });

    // Otherwise an admin could demote themselves out of the admin area.
    it('refuses to let an admin change their own role', function () {
        $admin = actingAsAdmin();

        $this->patchJson("/api/admin/users/{$admin->id}", ['role' => 'user'])
            ->assertStatus(422);

        expect($admin->refresh()->role)->toBe(Role::Admin);
    });

    it('lets an admin change their own name', function () {
        $admin = actingAsAdmin();

        $this->patchJson("/api/admin/users/{$admin->id}", ['name' => 'Still Admin'])->assertOk();

        expect($admin->refresh()->name)->toBe('Still Admin');
    });

    it('changes the status', function (string $status) {
        actingAsAdmin();
        $user = User::factory()->create();

        $this->patchJson("/api/admin/users/{$user->id}", ['status' => $status])
            ->assertOk()
            ->assertJsonPath('data.status', $status);

        expect($user->refresh()->status)->toBe(UserStatus::from($status));
    })->with(['suspended', 'inactive', 'active']);

    // A new address belongs to someone who has not proved they own it yet.
    it('clears verification when the email changes', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        $this->patchJson("/api/admin/users/{$user->id}", ['email' => 'moved@example.com'])->assertOk();

        expect($user->refresh()->email)->toBe('moved@example.com')
            ->and($user->hasVerifiedEmail())->toBeFalse();
    });

    it('keeps verification when the email is resubmitted unchanged', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        $this->patchJson("/api/admin/users/{$user->id}", ['email' => $user->email])->assertOk();

        expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    });

    it('hashes a new password', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        $this->patchJson("/api/admin/users/{$user->id}", [
            'password' => 'admin-set-password',
            'password_confirmation' => 'admin-set-password',
        ])->assertOk();

        $user->refresh();

        expect($user->password)->not->toBe('admin-set-password')
            ->and(Hash::check('admin-set-password', $user->password))->toBeTrue();
    });

    it('leaves the password alone when the field is sent empty', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        $this->patchJson("/api/admin/users/{$user->id}", ['password' => null])->assertOk();

        expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
    });

    it('replaces the avatar and deletes the old file', function () {
        actingAsAdmin();
        $user = User::factory()->create(['avatar' => 'avatars/old.jpg']);
        Storage::disk('public')->put('avatars/old.jpg', 'binary');

        $this->post("/api/admin/users/{$user->id}", [
            '_method' => 'PATCH',
            'avatar' => UploadedFile::fake()->image('new.jpg'),
        ])->assertOk();

        expect($user->refresh()->avatar)->toStartWith('avatars/')->not->toBe('avatars/old.jpg')
            ->and(Storage::disk('public')->exists('avatars/old.jpg'))->toBeFalse();
    });

    // A Google avatar is a remote URL, so there is no local file to remove.
    it('replaces an external Google avatar without trying to delete a file', function () {
        actingAsAdmin();
        $user = User::factory()->create(['avatar' => 'https://lh3.googleusercontent.com/a/photo.jpg']);

        $this->post("/api/admin/users/{$user->id}", [
            '_method' => 'PATCH',
            'avatar' => UploadedFile::fake()->image('new.jpg'),
        ])->assertOk();

        expect($user->refresh()->avatar)->toStartWith('avatars/');
    });

    it('rejects an avatar that is not an allowed image', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        // Accept header, so validation failures come back as JSON rather than a redirect.
        $this->post("/api/admin/users/{$user->id}", [
            '_method' => 'PATCH',
            'avatar' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('avatar');

        expect($user->refresh()->avatar)->toBeNull();
    });

    it('rejects invalid input', function (array $payload, string $field) {
        actingAsAdmin();
        $user = User::factory()->create();

        $this->patchJson("/api/admin/users/{$user->id}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with([
        'unknown role' => [['role' => 'superuser'], 'role'],
        'unknown status' => [['status' => 'banned'], 'status'],
        'blank name' => [['name' => ''], 'name'],
        'malformed email' => [['email' => 'not-an-email'], 'email'],
        'uppercase email' => [['email' => 'Mixed@Example.com'], 'email'],
        'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
        'unconfirmed password' => [['password' => 'long-enough-here', 'password_confirmation' => 'different'], 'password'],
    ]);

    it("rejects an email another account already uses", function () {
        actingAsAdmin();
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->patchJson("/api/admin/users/{$user->id}", ['email' => 'taken@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('denies a non-admin', function () {
        $target = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/admin/users/{$target->id}", ['name' => 'Hijacked'])->assertForbidden();

        expect($target->refresh()->name)->not->toBe('Hijacked');
    });
});

describe('deleting a user', function () {
    it('deletes another user', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        $this->deleteJson("/api/admin/users/{$user->id}")->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    it('refuses to let an admin delete their own account', function () {
        $admin = actingAsAdmin();

        $this->deleteJson("/api/admin/users/{$admin->id}")->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    });

    it('denies a non-admin', function () {
        $target = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/admin/users/{$target->id}")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    });

    it('requires authentication', function () {
        $target = User::factory()->create();

        $this->deleteJson("/api/admin/users/{$target->id}")->assertUnauthorized();
    });
});

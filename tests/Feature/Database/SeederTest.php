<?php

use App\Enums\BookLanguage;
use App\Enums\Role;
use App\Enums\TextDirection;
use App\Models\Book;
use App\Models\BookSetting;
use App\Models\Chapter;
use App\Models\User;
use Database\Seeders\BookSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

describe('UserSeeder', function () {
    it('creates the named demo accounts with a known password', function () {
        $this->seed(UserSeeder::class);

        $admin = User::where('email', 'admin@bookstudio.test')->sole();
        $demo = User::where('email', 'user@bookstudio.test')->sole();

        expect($admin->role)->toBe(Role::Admin)
            ->and($demo->role)->toBe(Role::User)
            ->and(Hash::check('password', $admin->password))->toBeTrue()
            ->and($admin->hasVerifiedEmail())->toBeTrue()
            ->and($demo->hasVerifiedEmail())->toBeTrue();
    });

    it('adds filler users alongside the named accounts', function () {
        $this->seed(UserSeeder::class);

        expect(User::count())->toBe(10);
    });

    // Re-running the seeder on an existing database must not duplicate the
    // named accounts or fail on the unique email index.
    it('is idempotent for the named accounts', function () {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        expect(User::where('email', 'admin@bookstudio.test')->count())->toBe(1)
            ->and(User::where('email', 'user@bookstudio.test')->count())->toBe(1);
    });
});

describe('BookSeeder', function () {
    beforeEach(function () {
        $this->seed(UserSeeder::class);
        $this->seed(BookSeeder::class);
    });

    it('gives both demo accounts books with chapters and settings', function () {
        foreach (['admin@bookstudio.test', 'user@bookstudio.test'] as $email) {
            $user = User::where('email', $email)->sole();

            expect($user->books)->not->toBeEmpty();

            foreach ($user->books as $book) {
                expect($book->chapters)->not->toBeEmpty()
                    ->and($book->setting)->not->toBeNull();
            }
        }
    });

    it('numbers each book\'s chapters from zero without gaps', function () {
        foreach (Book::with('chapters')->get() as $book) {
            expect($book->chapters->pluck('order')->all())
                ->toBe(range(0, $book->chapters->count() - 1));
        }
    });

    it('seeds one RTL Arabic book to exercise that path', function () {
        $arabic = BookSetting::where('language', BookLanguage::Ar)->sole();

        expect($arabic->text_direction)->toBe(TextDirection::Rtl)
            ->and($arabic->book->title)->toBe('رحلة في عالم الكتابة')
            ->and($arabic->book->chapters)->toHaveCount(3);
    });

    it('leaves no book without a settings row', function () {
        expect(Book::doesntHave('setting')->count())->toBe(0);
    });

    it('does nothing when the demo accounts are absent', function () {
        User::query()->delete();

        $this->seed(BookSeeder::class);

        expect(Book::count())->toBe(0);
    });
});

describe('DatabaseSeeder', function () {
    it('runs the whole chain', function () {
        $this->seed(DatabaseSeeder::class);

        expect(User::count())->toBeGreaterThan(0)
            ->and(Book::count())->toBeGreaterThan(0)
            ->and(Chapter::count())->toBeGreaterThan(0)
            ->and(BookSetting::count())->toBeGreaterThan(0);
    });

    it('produces a database the export endpoint can render', function () {
        $this->seed(DatabaseSeeder::class);

        $book = Book::has('chapters')->has('setting')->firstOrFail();

        $this->actingAs($book->user)
            ->get("/books/{$book->id}")
            ->assertOk();
    });
});

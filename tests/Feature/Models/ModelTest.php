<?php

use App\Enums\ContactCategory;
use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\Attachment;
use App\Models\Book;
use App\Models\BookSetting;
use App\Models\Chapter;
use App\Models\ContactMessage;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * @param  array<string, mixed>  $overrides
 */
function makeOtp(User $user, array $overrides = []): Otp
{
    return Otp::create([
        'user_id' => $user->id,
        'code' => Hash::make('123456'),
        'channel' => OtpChannel::Email,
        'purpose' => OtpPurpose::Verify,
        'target' => $user->email,
        'expires_at' => now()->addMinutes(10),
        ...$overrides,
    ]);
}

describe('User', function () {
    it('relates to its books and OTPs', function () {
        $user = User::factory()->create();
        Book::factory()->for($user)->count(2)->create();
        Otp::create([
            'user_id' => $user->id,
            'code' => Hash::make('123456'),
            'channel' => OtpChannel::Email,
            'purpose' => OtpPurpose::Verify,
            'target' => $user->email,
            'expires_at' => now()->addMinutes(10),
        ]);

        expect($user->books)->toHaveCount(2)
            ->and($user->otps)->toHaveCount(1)
            ->and($user->books->first()->user->is($user))->toBeTrue();
    });

    it('casts role and status to enums', function () {
        $user = User::factory()->admin()->create(['status' => UserStatus::Suspended]);

        expect($user->fresh()->role)->toBe(Role::Admin)
            ->and($user->fresh()->status)->toBe(UserStatus::Suspended);
    });

    it('defaults a new user to active before it is reloaded', function () {
        // UserResource reads status straight after save, so the attribute
        // default has to mirror the column default.
        expect((new User())->status)->toBe(UserStatus::Active);
    });

    it('reports admin and active status', function () {
        expect(User::factory()->admin()->create()->isAdmin())->toBeTrue()
            ->and(User::factory()->create()->isAdmin())->toBeFalse()
            ->and(User::factory()->create()->isActive())->toBeTrue()
            ->and(User::factory()->create(['status' => UserStatus::Inactive])->isActive())->toBeFalse();
    });

    it('hashes the password on assignment', function () {
        $user = User::factory()->create();
        $user->password = 'plain-text-password';
        $user->save();

        expect($user->password)->not->toBe('plain-text-password')
            ->and(Hash::check('plain-text-password', $user->fresh()->password))->toBeTrue();
    });

    it('hides the password and remember token from serialization', function () {
        expect(User::factory()->create()->toArray())
            ->not->toHaveKey('password')
            ->not->toHaveKey('remember_token');
    });

    it('does not mass assign role or status', function () {
        $user = User::create([
            'name' => 'Test',
            'email' => 'mass@example.com',
            'password' => 'a-password-here',
            'role' => Role::Admin,
            'status' => UserStatus::Suspended,
        ]);

        expect($user->fresh()->role)->not->toBe(Role::Admin)
            ->and($user->fresh()->status)->toBe(UserStatus::Active);
    });
});

describe('Book', function () {
    it('relates to its owner, chapters and setting', function () {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->has(Chapter::factory()->count(3), 'chapters')->create();
        $book->setting()->create();

        expect($book->user->is($user))->toBeTrue()
            ->and($book->chapters)->toHaveCount(3)
            ->and($book->setting)->toBeInstanceOf(BookSetting::class)
            ->and($book->setting->book->is($book))->toBeTrue();
    });

    it('always returns chapters in their stored order', function () {
        $book = Book::factory()->create();
        Chapter::factory()->for($book)->create(['title' => 'Last', 'order' => 9]);
        Chapter::factory()->for($book)->create(['title' => 'First', 'order' => 1]);

        expect($book->chapters->pluck('title')->all())->toBe(['First', 'Last']);
    });

    it('cascades deletes to chapters and settings', function () {
        $book = Book::factory()->has(Chapter::factory()->count(2), 'chapters')->create();
        $book->setting()->create();

        $book->delete();

        expect(Chapter::where('book_id', $book->id)->count())->toBe(0)
            ->and(BookSetting::where('book_id', $book->id)->count())->toBe(0);
    });

    it('cascades deletes from the owning user', function () {
        $user = User::factory()->create();
        Book::factory()->for($user)->count(2)->create();

        $user->delete();

        expect(Book::count())->toBe(0);
    });
});

describe('Chapter', function () {
    it('casts the order to an integer', function () {
        expect(Chapter::factory()->create(['order' => '4'])->fresh()->order)->toBe(4);
    });

    it('builds a stable PDF anchor from its id', function () {
        $chapter = Chapter::factory()->create();

        expect($chapter->pdfAnchor())->toBe("chapter-{$chapter->id}");
    });

    it('gives every chapter a distinct anchor', function () {
        $book = Book::factory()->create();
        $anchors = Chapter::factory()->for($book)->count(3)->create()
            ->map(fn (Chapter $chapter) => $chapter->pdfAnchor())->all();

        expect($anchors)->toBe(array_unique($anchors));
    });
});

describe('BookSetting', function () {
    it('casts its enum, float and boolean columns', function () {
        $setting = BookSetting::factory()->create()->fresh();

        expect($setting->font_size)->toBeFloat()
            ->and($setting->line_height)->toBeFloat()
            ->and($setting->chapter_header_font_size)->toBeFloat()
            ->and($setting->show_page_numbers)->toBeBool()
            ->and($setting->show_table_of_contents)->toBeBool()
            ->and($setting->show_chapter_headers)->toBeBool();
    });
});

describe('ContactMessage', function () {
    it('casts the category and read timestamp', function () {
        $message = ContactMessage::factory()->read()->ofCategory(ContactCategory::Bug)->create()->fresh();

        expect($message->category)->toBe(ContactCategory::Bug)
            ->and($message->read_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
    });

    it('relates to its attachments both ways', function () {
        $message = ContactMessage::factory()->create();
        $message->attachments()->create([
            'path' => 'contact-attachments/a.png',
            'original_name' => 'a.png',
            'mime_type' => 'image/png',
            'size' => 10,
        ]);

        $attachment = Attachment::sole();

        expect($message->attachments)->toHaveCount(1)
            ->and($attachment->contactMessages->first()->is($message))->toBeTrue();
    });

    it('does not mass assign the read timestamp', function () {
        $message = ContactMessage::create([
            'name' => 'A',
            'email' => 'a@example.com',
            'category' => ContactCategory::General,
            'message' => 'Hello',
            'read_at' => now(),
        ]);

        expect($message->fresh()->read_at)->toBeNull();
    });
});

describe('Otp scopes', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    it('filters to unused codes', function () {
        makeOtp($this->user);
        makeOtp($this->user, ['used_at' => now()]);

        expect($this->user->otps()->unused()->count())->toBe(1);
    });

    it('filters out expired codes', function () {
        makeOtp($this->user);
        makeOtp($this->user, ['expires_at' => now()->subMinute()]);

        expect($this->user->otps()->notExpired()->count())->toBe(1);
    });

    it('filters by purpose', function () {
        makeOtp($this->user);
        makeOtp($this->user, ['purpose' => OtpPurpose::Reset]);

        expect($this->user->otps()->forPurpose(OtpPurpose::Reset)->count())->toBe(1);
    });

    it('casts its enum and timestamp columns', function () {
        $otp = makeOtp($this->user, ['used_at' => now()])->fresh();

        expect($otp->channel)->toBe(OtpChannel::Email)
            ->and($otp->purpose)->toBe(OtpPurpose::Verify)
            ->and($otp->expires_at)->toBeInstanceOf(Carbon\CarbonInterface::class)
            ->and($otp->used_at)->toBeInstanceOf(Carbon\CarbonInterface::class)
            ->and($otp->user->is($this->user))->toBeTrue();
    });

    it('cascades deletes from the owning user', function () {
        makeOtp($this->user);

        $this->user->delete();

        expect(Otp::count())->toBe(0);
    });
});

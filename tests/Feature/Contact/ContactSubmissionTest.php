<?php

use App\Enums\ContactCategory;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function contactPayload(array $overrides = []): array
{
    return [
        'name' => 'Amelia Stone',
        'email' => 'amelia@example.com',
        'category' => 'bug',
        'message' => 'The export button does nothing on Firefox.',
        ...$overrides,
    ];
}

beforeEach(function () {
    Mail::fake();
    Storage::fake('public');
    config(['contact.recipient' => 'owner@example.com']);
});

describe('submitting the form', function () {
    it('stores the message and emails the site owner', function () {
        $this->postJson('/contact', contactPayload())->assertCreated();

        $message = ContactMessage::sole();

        expect($message->name)->toBe('Amelia Stone')
            ->and($message->email)->toBe('amelia@example.com')
            ->and($message->category)->toBe(ContactCategory::Bug)
            ->and($message->read_at)->toBeNull();

        Mail::assertSent(
            ContactMessageMail::class,
            fn (ContactMessageMail $mail) => $mail->hasTo('owner@example.com')
                && $mail->contactMessage->is($message)
        );
    });

    it('replies to the sender rather than the site owner', function () {
        $this->postJson('/contact', contactPayload())->assertCreated();

        Mail::assertSent(
            ContactMessageMail::class,
            fn (ContactMessageMail $mail) => $mail->hasReplyTo('amelia@example.com')
        );
    });

    it('accepts every category', function (string $category) {
        $this->postJson('/contact', contactPayload(['category' => $category]))->assertCreated();

        expect(ContactMessage::sole()->category)->toBe(ContactCategory::from($category));
    })->with(array_column(ContactCategory::cases(), 'value'));

    it('does not require authentication', function () {
        $this->assertGuest();

        $this->postJson('/contact', contactPayload())->assertCreated();
    });

    it('renders the contact page', function () {
        $this->get('/contact')->assertOk()->assertViewIs('contact');
    });
});

describe('attachments', function () {
    it('stores an attachment with its original metadata', function () {
        $this->post('/contact', contactPayload([
            'attachments' => [UploadedFile::fake()->image('screenshot.png')->size(64)],
        ]))->assertCreated();

        $attachment = ContactMessage::sole()->attachments()->sole();

        expect($attachment->original_name)->toBe('screenshot.png')
            ->and($attachment->mime_type)->toBe('image/png')
            ->and($attachment->size)->toBe(64 * 1024)
            ->and($attachment->path)->toStartWith('contact-attachments/')
            ->and(Storage::disk('public')->exists($attachment->path))->toBeTrue();
    });

    it('stores several attachments at once', function () {
        $this->post('/contact', contactPayload([
            'attachments' => [
                UploadedFile::fake()->image('one.png'),
                UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ],
        ]))->assertCreated();

        expect(ContactMessage::sole()->attachments)->toHaveCount(2);
    });

    it('accepts a submission with no attachments', function () {
        $this->postJson('/contact', contactPayload())->assertCreated();

        expect(ContactMessage::sole()->attachments)->toBeEmpty();
    });

    it('rejects more than five attachments', function () {
        $this->postJson('/contact', contactPayload([
            'attachments' => array_map(fn ($i) => UploadedFile::fake()->image("shot{$i}.png"), range(1, 6)),
        ]))->assertUnprocessable()->assertJsonValidationErrors('attachments');

        expect(ContactMessage::count())->toBe(0);
    });

    it('rejects a disallowed file type', function () {
        $this->postJson('/contact', contactPayload([
            'attachments' => [UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload')],
        ]))->assertUnprocessable()->assertJsonValidationErrors('attachments.0');
    });

    it('rejects an attachment over the size cap', function () {
        $this->postJson('/contact', contactPayload([
            'attachments' => [UploadedFile::fake()->image('huge.png')->size(6000)],
        ]))->assertUnprocessable()->assertJsonValidationErrors('attachments.0');
    });

    it('stores nothing when one attachment is rejected', function () {
        $this->postJson('/contact', contactPayload([
            'attachments' => [
                UploadedFile::fake()->image('fine.png'),
                UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload'),
            ],
        ]))->assertUnprocessable();

        expect(ContactMessage::count())->toBe(0)
            ->and(Storage::disk('public')->allFiles())->toBeEmpty();
    });
});

describe('validation', function () {
    it('rejects invalid input', function (array $payload, string $field) {
        $this->postJson('/contact', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        expect(ContactMessage::count())->toBe(0);
    })->with([
        'empty' => [[], 'name'],
        'missing name' => [[...contactPayload(), 'name' => null], 'name'],
        'missing email' => [[...contactPayload(), 'email' => null], 'email'],
        'malformed email' => [[...contactPayload(), 'email' => 'not-an-email'], 'email'],
        'missing category' => [[...contactPayload(), 'category' => null], 'category'],
        'unknown category' => [[...contactPayload(), 'category' => 'complaint'], 'category'],
        'missing message' => [[...contactPayload(), 'message' => null], 'message'],
        'overlong name' => [[...contactPayload(), 'name' => str_repeat('a', 256)], 'name'],
        'overlong message' => [[...contactPayload(), 'message' => str_repeat('a', 5001)], 'message'],
    ]);

    it('accepts a message at the length limit', function () {
        $this->postJson('/contact', contactPayload(['message' => str_repeat('a', 5000)]))
            ->assertCreated();
    });
});

describe('mail delivery failures', function () {
    // The record is persisted before the email is attempted, so a submission is
    // never lost when the mailer is down — it still reaches the admin inbox.
    it('keeps the message and still reports success when sending throws', function () {
        Log::spy();

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unreachable'));

        $this->postJson('/contact', contactPayload())->assertCreated();

        expect(ContactMessage::count())->toBe(1);

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message) => str_contains($message, 'Failed to send contact message email'));
    });

    it('keeps stored attachments when sending throws', function () {
        Log::spy();

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unreachable'));

        $this->post('/contact', contactPayload([
            'attachments' => [UploadedFile::fake()->image('screenshot.png')],
        ]))->assertCreated();

        expect(ContactMessage::sole()->attachments)->toHaveCount(1);
    });
});

describe('throttling', function () {
    it('blocks a seventh submission within the minute', function () {
        foreach (range(1, 6) as $i) {
            $this->postJson('/contact', contactPayload())->assertCreated();
        }

        $this->postJson('/contact', contactPayload())->assertStatus(429);

        expect(ContactMessage::count())->toBe(6);
    });
});

<?php

use App\Enums\ContactCategory;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * A message with one stored attachment, wired through the same morph relation
 * the contact controller uses.
 */
function messageWithAttachment(string $path = 'contact-attachments/screenshot.png'): ContactMessage
{
    $message = ContactMessage::factory()->create();

    Storage::disk('public')->put($path, 'binary');

    $message->attachments()->create([
        'path' => $path,
        'original_name' => 'screenshot.png',
        'mime_type' => 'image/png',
        'size' => 1024,
    ]);

    return $message;
}

beforeEach(function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->admin()->create());
});

describe('listing messages', function () {
    it('returns a paginated list with attachments', function () {
        messageWithAttachment();
        ContactMessage::factory()->count(2)->create();

        $this->getJson('/api/admin/messages')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'category', 'message', 'attachments', 'read_at', 'created_at']],
                'meta' => ['current_page', 'total'],
            ]);
    });

    it('exposes attachment metadata and flags images', function () {
        messageWithAttachment();

        $attachment = $this->getJson('/api/admin/messages')->assertOk()->json('data.0.attachments.0');

        expect($attachment)->toMatchArray([
            'original_name' => 'screenshot.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'is_image' => true,
        ])->and($attachment['url'])->toContain('contact-attachments/screenshot.png');
    });

    it('marks a non-image attachment as not an image', function () {
        $message = ContactMessage::factory()->create();
        $message->attachments()->create([
            'path' => 'contact-attachments/notes.pdf',
            'original_name' => 'notes.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);

        $this->getJson('/api/admin/messages')
            ->assertOk()
            ->assertJsonPath('data.0.attachments.0.is_image', false);
    });

    it('lists the newest messages first', function () {
        ContactMessage::factory()->create(['name' => 'Older', 'created_at' => now()->subDay()]);
        ContactMessage::factory()->create(['name' => 'Newest', 'created_at' => now()->addDay()]);

        expect($this->getJson('/api/admin/messages')->assertOk()->json('data.0.name'))->toBe('Newest');
    });

    it('filters by category', function () {
        ContactMessage::factory()->ofCategory(ContactCategory::Bug)->count(2)->create();
        ContactMessage::factory()->ofCategory(ContactCategory::Feature)->create();

        $this->getJson('/api/admin/messages?category=bug')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.category', 'bug');
    });

    it('filters to unread messages', function () {
        ContactMessage::factory()->count(2)->create();
        ContactMessage::factory()->read()->count(3)->create();

        $this->getJson('/api/admin/messages?unread=1')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.read_at', null);
    });

    it('filters to read messages', function () {
        ContactMessage::factory()->count(2)->create();
        ContactMessage::factory()->read()->count(3)->create();

        $response = $this->getJson('/api/admin/messages?unread=0')->assertOk()->assertJsonCount(3, 'data');

        expect($response->json('data.0.read_at'))->not->toBeNull();
    });

    it('returns every message when the unread filter is absent', function () {
        ContactMessage::factory()->count(2)->create();
        ContactMessage::factory()->read()->count(3)->create();

        $this->getJson('/api/admin/messages')->assertOk()->assertJsonCount(5, 'data');
    });

    it('honours per_page', function () {
        ContactMessage::factory()->count(8)->create();

        $this->getJson('/api/admin/messages?per_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 8);
    });

    it('rejects invalid filters', function (string $query, string $field) {
        $this->getJson("/api/admin/messages?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with([
        'unknown category' => ['category=complaint', 'category'],
        'non-boolean unread' => ['unread=maybe', 'unread'],
        'per_page above the cap' => ['per_page=500', 'per_page'],
    ]);
});

describe('message details', function () {
    it('returns a single message with its attachments', function () {
        $message = messageWithAttachment();

        $this->getJson("/api/admin/messages/{$message->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $message->id)
            ->assertJsonCount(1, 'data.attachments');
    });

    it('404s for an unknown message', function () {
        $this->getJson('/api/admin/messages/999999')->assertNotFound();
    });
});

describe('marking read and unread', function () {
    it('marks a message read', function () {
        $message = ContactMessage::factory()->create();

        $this->patchJson("/api/admin/messages/{$message->id}", ['read' => true])->assertOk();

        expect($message->refresh()->read_at)->not->toBeNull();
    });

    it('marks a message unread again', function () {
        $message = ContactMessage::factory()->read()->create();

        $this->patchJson("/api/admin/messages/{$message->id}", ['read' => false])
            ->assertOk()
            ->assertJsonPath('data.read_at', null);

        expect($message->refresh()->read_at)->toBeNull();
    });

    it('requires the read flag', function () {
        $message = ContactMessage::factory()->create();

        $this->patchJson("/api/admin/messages/{$message->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('read');
    });

    it('rejects a non-boolean read flag', function () {
        $message = ContactMessage::factory()->create();

        $this->patchJson("/api/admin/messages/{$message->id}", ['read' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('read');
    });
});

describe('deleting a message', function () {
    it('deletes the message along with its attachment rows and files', function () {
        $message = messageWithAttachment();
        $attachmentId = $message->attachments()->sole()->id;

        $this->deleteJson("/api/admin/messages/{$message->id}")->assertOk();

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attachmentId]);

        expect(Storage::disk('public')->exists('contact-attachments/screenshot.png'))->toBeFalse();
    });

    it('deletes a message that has no attachments', function () {
        $message = ContactMessage::factory()->create();

        $this->deleteJson("/api/admin/messages/{$message->id}")->assertOk();

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    });

    it('leaves other messages and their files untouched', function () {
        $target = messageWithAttachment('contact-attachments/target.png');
        messageWithAttachment('contact-attachments/keep.png');

        $this->deleteJson("/api/admin/messages/{$target->id}")->assertOk();

        expect(Storage::disk('public')->exists('contact-attachments/keep.png'))->toBeTrue()
            ->and(ContactMessage::count())->toBe(1);
    });
});

describe('authorization', function () {
    it('denies a non-admin every endpoint', function () {
        $message = ContactMessage::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/messages')->assertForbidden();
        $this->getJson("/api/admin/messages/{$message->id}")->assertForbidden();
        $this->patchJson("/api/admin/messages/{$message->id}", ['read' => true])->assertForbidden();
        $this->deleteJson("/api/admin/messages/{$message->id}")->assertForbidden();

        $this->assertDatabaseHas('contact_messages', ['id' => $message->id]);
    });

    it('requires authentication', function () {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/admin/messages')->assertUnauthorized();
    });
});

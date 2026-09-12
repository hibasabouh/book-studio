<?php

use App\Enums\ContactCategory;
use App\Mail\ContactMessageMail;
use App\Mail\OtpMail;
use App\Models\ContactMessage;

describe('OtpMail', function () {
    it('renders the code into the body', function () {
        $mail = new OtpMail('654321');

        $mail->assertSeeInHtml('654321');
    });

    it('has a subject', function () {
        expect((new OtpMail('654321'))->envelope()->subject)->toBeString()->not->toBe('');
    });

    it('renders in every supported locale', function (string $locale) {
        app()->setLocale($locale);

        (new OtpMail('654321'))->assertSeeInHtml('654321');
    })->with(['en', 'ar', 'fr']);
});

describe('ContactMessageMail', function () {
    beforeEach(function () {
        $this->message = ContactMessage::factory()->create([
            'name' => 'Amelia Stone',
            'email' => 'amelia@example.com',
            'category' => ContactCategory::Bug,
            'message' => 'The export button does nothing.',
        ]);
    });

    it('renders the sender and the message body', function () {
        $mail = new ContactMessageMail($this->message->load('attachments'));

        $mail->assertSeeInHtml('Amelia Stone');
        $mail->assertSeeInHtml('amelia@example.com');
        $mail->assertSeeInHtml('The export button does nothing.');
    });

    it('names the category in the subject', function () {
        $envelope = (new ContactMessageMail($this->message))->envelope();

        expect($envelope->subject)->toContain(ContactCategory::Bug->label());
    });

    // So the site owner can reply straight to the person who wrote in.
    it('replies to the sender', function () {
        expect((new ContactMessageMail($this->message))->envelope()->replyTo[0]->address)
            ->toBe('amelia@example.com');
    });

    it('escapes HTML in the message body', function () {
        $this->message->update(['message' => '<script>alert(1)</script>']);

        $mail = new ContactMessageMail($this->message->load('attachments'));

        // escape: false searches for the raw tag, which must not survive.
        $mail->assertDontSeeInHtml('<script>alert(1)</script>', false);
        $mail->assertSeeInHtml('<script>alert(1)</script>');
    });

    it('escapes HTML in the sender name', function () {
        $this->message->update(['name' => '<b>Injected</b>']);

        (new ContactMessageMail($this->message->load('attachments')))
            ->assertDontSeeInHtml('<b>Injected</b>', false);
    });

    it('lists the attachments it was given', function () {
        $this->message->attachments()->create([
            'path' => 'contact-attachments/screenshot.png',
            'original_name' => 'screenshot.png',
            'mime_type' => 'image/png',
            'size' => 1024,
        ]);

        (new ContactMessageMail($this->message->load('attachments')))
            ->assertSeeInHtml('screenshot.png');
    });

    it('renders with no attachments', function () {
        (new ContactMessageMail($this->message->load('attachments')))
            ->assertSeeInHtml('Amelia Stone');
    });

    it('renders every category', function (ContactCategory $category) {
        $this->message->update(['category' => $category]);

        (new ContactMessageMail($this->message->load('attachments')))
            ->assertSeeInHtml('Amelia Stone');
    })->with(ContactCategory::cases());
});

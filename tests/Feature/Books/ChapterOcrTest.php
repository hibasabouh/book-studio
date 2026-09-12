<?php

use App\Models\Book;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

const OCR_ENDPOINT = 'https://api.ocr.space/parse/image';

function fakeOcr(array $payload, int $status = 200): void
{
    Http::fake([OCR_ENDPOINT => Http::response($payload, $status)]);
}

function ocrSuccess(string $text): void
{
    fakeOcr(['IsErroredOnProcessing' => false, 'ParsedResults' => [['ParsedText' => $text, 'ErrorMessage' => '']]]);
}

function pageImage(): UploadedFile
{
    return UploadedFile::fake()->image('page.jpg');
}

beforeEach(function () {
    config(['services.ocrspace.key' => 'test-key', 'services.ocrspace.endpoint' => OCR_ENDPOINT]);

    Sanctum::actingAs($this->owner = User::factory()->create());
    $this->chapter = Chapter::factory()->for(Book::factory()->for($this->owner))->create();
});

describe('extracting text', function () {
    it('returns the text OCR.space parsed out of the image', function () {
        ocrSuccess("Once upon a time.\nThe end.");

        $this->post("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])
            ->assertOk()
            ->assertJsonPath('text', "Once upon a time.\nThe end.");
    });

    it('trims surrounding whitespace from the parsed text', function () {
        ocrSuccess("\n\n  Body text.  \n\n");

        $this->post("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])
            ->assertOk()
            ->assertJsonPath('text', 'Body text.');
    });

    it('returns an empty string when the page held no text', function () {
        ocrSuccess('');

        $this->post("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])
            ->assertOk()
            ->assertJsonPath('text', '');
    });

    it('sends the API key and defaults to English', function () {
        ocrSuccess('Text');

        $this->post("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])->assertOk();

        Http::assertSent(function (Request $request) {
            $fields = collect($request->data())->pluck('contents', 'name');

            return $request->hasHeader('apikey', 'test-key')
                && $request->url() === OCR_ENDPOINT
                && $fields['language'] === 'eng'
                && $fields['OCREngine'] === 2;
        });
    });

    it('forwards the requested language', function () {
        ocrSuccess('نص');

        $this->post("/api/chapters/{$this->chapter->id}/ocr", [
            'image' => pageImage(),
            'language' => 'ara',
        ])->assertOk();

        Http::assertSent(fn (Request $request) => collect($request->data())
            ->pluck('contents', 'name')['language'] === 'ara');
    });

    it('accepts a PDF page as well as an image', function () {
        ocrSuccess('From a PDF.');

        $this->post("/api/chapters/{$this->chapter->id}/ocr", [
            'image' => UploadedFile::fake()->create('scan.pdf', 20, 'application/pdf'),
        ])->assertOk()->assertJsonPath('text', 'From a PDF.');
    });
});

describe('OCR failures', function () {
    it('reports a validation error when the service flags a processing error', function () {
        Log::spy();
        fakeOcr(['IsErroredOnProcessing' => true, 'ErrorMessage' => 'File format not supported']);

        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    });

    it('reports a validation error when the parsed result carries an error', function () {
        Log::spy();
        fakeOcr([
            'IsErroredOnProcessing' => false,
            'ParsedResults' => [['ParsedText' => '', 'ErrorMessage' => 'Unable to recognise text']],
        ]);

        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    });

    it('reports a validation error when no results come back at all', function () {
        Log::spy();
        fakeOcr(['IsErroredOnProcessing' => false, 'ParsedResults' => []]);

        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    });

    it('reports a validation error on a non-JSON error body', function () {
        Log::spy();
        Http::fake([OCR_ENDPOINT => Http::response('Service Unavailable', 503)]);

        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    });

    it('logs the failure so the cause is diagnosable', function () {
        Log::spy();
        fakeOcr(['IsErroredOnProcessing' => true, 'ErrorMessage' => 'Quota exceeded']);

        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])
            ->assertUnprocessable();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => str_contains($message, 'OCR.space extraction failed'));
    });
});

describe('validation', function () {
    it('requires an image', function () {
        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        Http::assertNothingSent();
    });

    it('rejects a disallowed file type', function () {
        Http::fake();

        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", [
            'image' => UploadedFile::fake()->create('notes.docx', 10, 'application/msword'),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');

        Http::assertNothingSent();
    });

    it('rejects a file over the 1 MB cap', function () {
        Http::fake();

        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", [
            'image' => UploadedFile::fake()->image('huge.jpg')->size(2048),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');
    });

    it('rejects a language code that is not three letters', function () {
        Http::fake();

        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", [
            'image' => pageImage(),
            'language' => 'en',
        ])->assertUnprocessable()->assertJsonValidationErrors('language');
    });
});

describe('authorization', function () {
    it("denies running OCR against someone else's chapter", function () {
        Http::fake();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])
            ->assertForbidden();

        Http::assertNothingSent();
    });

    it("lets an admin run OCR on any chapter", function () {
        ocrSuccess('Admin text');

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->post("/api/chapters/{$this->chapter->id}/ocr", ['image' => pageImage()])->assertOk();
    });

    it('requires authentication', function () {
        $this->app['auth']->forgetGuards();

        $this->postJson("/api/chapters/{$this->chapter->id}/ocr", [])->assertUnauthorized();
    });
});

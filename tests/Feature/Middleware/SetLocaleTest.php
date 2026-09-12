<?php

use Illuminate\Support\Facades\Route;

/**
 * The middleware only sets the locale, so an explicit probe route is the
 * clearest way to observe what it resolved.
 */
beforeEach(function () {
    foreach (['web', 'api'] as $stack) {
        Route::middleware($stack)->get("/_probe/{$stack}/locale", fn () => response()->json([
            'locale' => app()->getLocale(),
        ]));
    }
});

describe('resolving the locale from X-Locale', function () {
    it('applies a supported locale', function (string $stack, string $locale) {
        $this->getJson("/_probe/{$stack}/locale", ['X-Locale' => $locale])
            ->assertOk()
            ->assertJsonPath('locale', $locale);
    })->with(['web', 'api'])->with(['en', 'ar', 'fr']);

    it('falls back to the app default when the header is absent', function (string $stack) {
        $this->getJson("/_probe/{$stack}/locale")
            ->assertOk()
            ->assertJsonPath('locale', config('app.locale'));
    })->with(['web', 'api']);

    it('ignores a locale that is not on the supported list', function (string $header) {
        $this->getJson('/_probe/api/locale', ['X-Locale' => $header])
            ->assertOk()
            ->assertJsonPath('locale', config('app.locale'));
    })->with([
        'unsupported language' => 'de',
        'regional variant' => 'en-GB',
        'empty' => '',
        'nonsense' => 'not-a-locale',
    ]);

    // The list is compared strictly, so a value that loosely equals an entry
    // must not slip through.
    it('ignores a numeric header value', function () {
        $this->getJson('/_probe/api/locale', ['X-Locale' => '0'])
            ->assertOk()
            ->assertJsonPath('locale', config('app.locale'));
    });

    it('does not leak the locale from one request into the next', function () {
        $this->getJson('/_probe/api/locale', ['X-Locale' => 'ar'])->assertJsonPath('locale', 'ar');

        $this->getJson('/_probe/api/locale')->assertJsonPath('locale', config('app.locale'));
    });
});

describe('translated responses', function () {
    it('returns validation messages in the requested locale', function () {
        $english = $this->postJson('/api/login', [], ['X-Locale' => 'en'])
            ->assertUnprocessable()
            ->json('errors.email.0');

        $arabic = $this->postJson('/api/login', [], ['X-Locale' => 'ar'])
            ->assertUnprocessable()
            ->json('errors.email.0');

        expect($arabic)->not->toBe($english);
    });
});

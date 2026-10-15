<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('api')->group(function () {
        Route::get('/_probe/forbidden', fn () => throw new AuthorizationException);
        Route::get('/_probe/custom-forbidden', fn () => abort(403, 'Custom reason.'));
        Route::get('/_probe/crash', fn () => throw new RuntimeException('internal detail'));
        Route::get('/_probe/auth', fn () => 'ok')->middleware('auth:sanctum');
    });
});

it('translates framework error messages using the X-Locale header', function (string $method, string $uri, int $status, string $key) {
    $this->json($method, $uri, headers: ['X-Locale' => 'fr'])
        ->assertStatus($status)
        ->assertExactJson(['message' => trans($key, locale: 'fr')]);
})->with([
    'unauthenticated' => ['GET', '/_probe/auth', 401, 'messages.unauthenticated'],
    'forbidden' => ['GET', '/_probe/forbidden', 403, 'messages.forbidden'],
    'unknown route' => ['GET', '/_probe/missing', 404, 'messages.not_found'],
    'wrong method' => ['POST', '/_probe/forbidden', 405, 'messages.method_not_allowed'],
]);

it('keeps messages the app passed to abort()', function () {
    $this->getJson('/_probe/custom-forbidden')
        ->assertForbidden()
        ->assertJsonPath('message', 'Custom reason.');
});

it('hides unexpected error details when debug is off', function () {
    config(['app.debug' => false]);

    $this->getJson('/_probe/crash', ['X-Locale' => 'ar'])
        ->assertStatus(500)
        ->assertExactJson(['message' => trans('messages.server_error', locale: 'ar')]);
});

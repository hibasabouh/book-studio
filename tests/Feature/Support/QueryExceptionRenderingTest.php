<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config(['app.debug' => true]);

    Route::get('/_probe/query-error', function () {
        throw new QueryException('mysql', 'update `chapters` set `content` = ?', ['secret'], new PDOException('MySQL server has gone away'));
    });
});

it('hides SQL from JSON clients even in debug mode', function () {
    $response = $this->getJson('/_probe/query-error')
        ->assertStatus(500)
        ->assertExactJson(['message' => __('messages.server_error')]);

    expect($response->getContent())->not->toContain('chapters')->not->toContain('gone away');
});

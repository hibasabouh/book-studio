<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowBookSettingRequest;
use App\Http\Requests\UpdateBookSettingRequest;
use App\Http\Resources\BookSettingResource;
use App\Models\Book;

class BookSettingController extends Controller
{
    /**
     * A book's layout/typography settings.
     */
    public function show(ShowBookSettingRequest $request, Book $book): BookSettingResource
    {
        return new BookSettingResource($book->setting);
    }

    /**
     * Update a book's layout/typography settings.
     */
    public function update(UpdateBookSettingRequest $request, Book $book): BookSettingResource
    {
        $book->setting->update($request->validated());

        return new BookSettingResource($book->setting);
    }
}

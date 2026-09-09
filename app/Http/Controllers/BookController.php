<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyBookCoverRequest;
use App\Http\Requests\DestroyBookRequest;
use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\ShowBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookCoverRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookCollection;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\CoverImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    /**
     * Library: the user's books as JSON, or the Blade grid shell for the browser.
     */
    public function index(IndexBookRequest $request): BookCollection|View
    {
        // Web page is a thin shell; it fetches /api/books itself with a token.
        if (! $request->expectsJson()) {
            return view('books.index');
        }

        $validated = $request->validated();
        $perPage   = (int) ($validated['per_page'] ?? 15);

        $books = $request->user()
            ->books()
            ->withCount('chapters')
            ->when($validated['name'] ?? null, fn ($q, $name) => $q->where('title', 'like', "%{$name}%"))
            ->latest()
            ->paginate($perPage);

        return new BookCollection($books);
    }

    /**
     * Create a book and its default settings row.
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = $request->user()->books()->create($request->validated());
        $book->setting()->create();

        return (new BookResource($book->loadCount('chapters')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ShowBookRequest $request, Book $book): BookResource|View
    {
        if (! $request->expectsJson()) {
            return view('books.show', ['book' => $book]);
        }

        return new BookResource($book->loadCount('chapters'));
    }

    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $book->update($request->validated());

        return new BookResource($book->loadCount('chapters'));
    }

    public function destroy(DestroyBookRequest $request, Book $book): JsonResponse
    {
        $book->delete();

        delete_file_if_exist($book->cover_path);

        return response()->json(['message' => __('messages.book_deleted')]);
    }

    public function updateCover(UpdateBookCoverRequest $request, Book $book, CoverImageService $covers): BookResource
    {
        $previous = $book->cover_path;

        $book->cover_path = $covers->store($request->file('cover'), $request->crop());
        $book->save();

        delete_file_if_exist($previous);

        return new BookResource($book->loadCount('chapters'));
    }

    public function destroyCover(DestroyBookCoverRequest $request, Book $book): BookResource
    {
        delete_file_if_exist($book->cover_path);

        $book->cover_path = null;
        $book->save();

        return new BookResource($book->loadCount('chapters'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyChapterRequest;
use App\Http\Requests\IndexChapterRequest;
use App\Http\Requests\OcrChapterRequest;
use App\Http\Requests\ReorderChaptersRequest;
use App\Http\Requests\ShowChapterRequest;
use App\Http\Requests\StoreChapterRequest;
use App\Http\Requests\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Models\Book;
use App\Models\Chapter;
use App\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChapterController extends Controller
{
    /**
     * A book's chapters, in their current display order.
     */
    public function index(IndexChapterRequest $request, Book $book): AnonymousResourceCollection
    {
        return ChapterResource::collection($book->chapters);
    }

    /**
     * Add a new chapter to a book, appended after its existing chapters.
     */
    public function store(StoreChapterRequest $request, Book $book): JsonResponse
    {
        $order   = (int) $book->chapters()->max('order') + 1;
        $chapter = $book->chapters()->create([...$request->validated(), 'order' => $order]);

        return (new ChapterResource($chapter))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * A single chapter's details.
     */
    public function show(ShowChapterRequest $request, Chapter $chapter): ChapterResource
    {
        return new ChapterResource($chapter);
    }

    /**
     * Update a chapter's title and/or content.
     */
    public function update(UpdateChapterRequest $request, Chapter $chapter): ChapterResource
    {
        $chapter->update($request->validated());

        return new ChapterResource($chapter);
    }

    /**
     * Delete a chapter.
     */
    public function destroy(DestroyChapterRequest $request, Chapter $chapter): JsonResponse
    {
        $chapter->delete();

        return response()->json(['message' => __('messages.chapter_deleted')]);
    }

    /**
     * Reorder a book's chapters to match the given sequence of chapter IDs.
     */
    public function reorder(ReorderChaptersRequest $request, Book $book): JsonResponse
    {
        $ids   = $request->validated()['ids'];
        $owned = $book->chapters()->pluck('id')->toArray();

        foreach ($ids as $order => $id) {
            if (in_array($id, $owned)) {
                Chapter::where('id', $id)->update(['order' => $order]);
            }
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Extract text from an uploaded image via OCR, for pasting into the chapter.
     */
    public function ocr(OcrChapterRequest $request, Chapter $chapter, OcrService $ocr): JsonResponse
    {
        $text = $ocr->extractText($request->file('image'), $request->validated('language'));

        return response()->json(['text' => $text]);
    }
}

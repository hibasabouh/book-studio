<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportBookRequest;
use App\Http\Requests\ShowBookExportRequest;
use App\Http\Resources\BookExportResource;
use App\Jobs\GenerateBookPdf;
use App\Models\Book;
use App\Models\BookExport;
use App\Services\BookExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookExportController extends Controller
{
    /**
     * Queue a PDF export of the book. Poll the returned export until its status is
     * `completed` (then fetch `download_url`) or `failed` (then show `error`).
     *
     * An export already queued or running for this book is returned instead of a new one.
     *
     * @status 202
     */
    public function store(ExportBookRequest $request, Book $book): JsonResponse
    {
        $export = $book->exports()->active()->where('user_id', $request->user()->id)->latest()->first();

        if (! $export) {
            $export = $book->exports()->create(['user_id' => $request->user()->id]);
            GenerateBookPdf::dispatch($export);
        }

        return (new BookExportResource($export->refresh()))->response()->setStatusCode(202);
    }

    /**
     * Current status of a queued export.
     */
    public function show(ShowBookExportRequest $request, BookExport $export): BookExportResource
    {
        return new BookExportResource($export);
    }

    /**
     * Download a completed export as a PDF file.
     *
     * Responds 409 while the export is still running or has failed, and 410 once its
     * file has been cleaned up.
     */
    public function download(ShowBookExportRequest $request, BookExport $export): StreamedResponse|JsonResponse
    {
        if (! $export->isCompleted()) {
            return response()->json(['message' => __('messages.export_not_ready')], 409);
        }

        if (! $export->fileExists()) {
            return response()->json(['message' => __('messages.export_expired')], 410);
        }

        $title = $export->book->title;
        $safeTitle = trim(preg_replace('/[\/\\\\:*?"<>|]+/u', '', $title));
        $asciiFilename = (Str::slug($title, '-') ?: 'book').'.pdf';
        $utf8Filename = ($safeTitle ?: 'book').'.pdf';

        return Storage::disk(BookExport::DISK)->response($export->path, null, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$asciiFilename}\"; filename*=UTF-8''".rawurlencode($utf8Filename),
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * The book rendered to PDF on the spot, base64-encoded in JSON to avoid PDF interception by browser viewers and extensions.
     */
    public function preview(ExportBookRequest $request, Book $book, BookExportService $service): JsonResponse
    {
        return response()
            ->json(['pdf' => base64_encode($service->generatePdf($book))])
            // Rebuilt from the book on every request, so a cached copy is always stale.
            ->header('Cache-Control', 'no-store, private');
    }
}

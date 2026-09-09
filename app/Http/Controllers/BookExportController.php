<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportBookRequest;
use App\Models\Book;
use App\Services\BookExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BookExportController extends Controller
{
    public function download(ExportBookRequest $request, Book $book, BookExportService $service): Response
    {
        $pdf = $this->generate($book, $service);

        $safeTitle = trim(preg_replace('/[\/\\\\:*?"<>|]+/u', '', $book->title));
        $asciiFilename = (Str::slug($book->title, '-') ?: 'book').'.pdf';
        $utf8Filename = ($safeTitle ?: 'book').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$asciiFilename}\"; filename*=UTF-8''".rawurlencode($utf8Filename),
            // Rebuilt from the book on every request, so a cached copy is always stale.
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * The same document as the download, but base64 inside JSON rather than a
     * PDF body. Anything that claims application/pdf gets intercepted on its way
     * to the page — browser PDF viewers, download managers, PDF extensions — so
     * the preview never receives the bytes. JSON is left alone.
     */
    public function preview(ExportBookRequest $request, Book $book, BookExportService $service): JsonResponse
    {
        $pdf = $this->generate($book, $service);

        return response()
            ->json(['pdf' => base64_encode($pdf)])
            ->header('Cache-Control', 'no-store, private');
    }

    private function generate(Book $book, BookExportService $service): string
    {
        $book->load(['chapters', 'setting']);

        return $service->generatePdf($book);
    }
}

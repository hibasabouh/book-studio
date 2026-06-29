<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportBookRequest;
use App\Models\Book;
use App\Services\BookExportService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BookExportController extends Controller
{
    public function download(ExportBookRequest $request, Book $book, BookExportService $service): Response
    {
        $book->load(['chapters', 'setting']);

        $pdf = $service->generatePdf($book);

        $filename = Str::slug($book->title, '-').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

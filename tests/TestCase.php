<?php

namespace Tests;

use App\Models\Book;
use App\Models\BookExport;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * Queue an export of the book and return its download response. The test
     * queue is `sync`, so the job has already finished when the POST returns.
     */
    public function exportBook(Book $book): TestResponse
    {
        Storage::fake(BookExport::DISK);

        $id = $this->postJson("/api/books/{$book->id}/exports")->assertAccepted()->json('data.id');

        return $this->get("/api/exports/{$id}/download");
    }
}

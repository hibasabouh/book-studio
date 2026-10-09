<?php

namespace App\Jobs;

use App\Enums\ExportFailure;
use App\Models\BookExport;
use App\Services\BookExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateBookPdf implements ShouldQueue
{
    use Queueable;

    // A failed render is reported to the user, who can retry with fixed content.
    public int $tries = 1;

    // Must stay below the queue connection's retry_after (config/queue.php).
    public int $timeout = 120;

    public bool $failOnTimeout = true;

    // The book (and its export rows) was deleted before the job ran.
    public bool $deleteWhenMissingModels = true;

    public function __construct(public BookExport $export) {}

    public function handle(BookExportService $service): void
    {
        $this->export->markProcessing();

        $pdf = $service->generatePdf($this->export->book);

        $path = "exports/{$this->export->id}.pdf";
        Storage::disk(BookExport::DISK)->put($path, $pdf);

        $this->export->markCompleted($path);
    }

    public function failed(?Throwable $exception): void
    {
        $this->export->markFailed(ExportFailure::fromException($exception));
    }
}

<?php

namespace Database\Factories;

use App\Enums\ExportFailure;
use App\Enums\ExportStatus;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\BookExport>
 */
class BookExportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'user_id' => fn (array $attributes) => Book::find($attributes['book_id'])->user_id,
            'status' => ExportStatus::Pending,
        ];
    }

    public function completed(string $path = 'exports/book.pdf'): static
    {
        return $this->state(['status' => ExportStatus::Completed, 'path' => $path]);
    }

    public function failed(ExportFailure $failure = ExportFailure::Unexpected): static
    {
        return $this->state(['status' => ExportStatus::Failed, 'failure' => $failure]);
    }
}

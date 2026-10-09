<?php

namespace App\Models;

use App\Enums\ExportFailure;
use App\Enums\ExportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BookExport extends Model
{
    /** @use HasFactory<\Database\Factories\BookExportFactory> */
    use HasFactory, Prunable;

    public const DISK = 'local';

    protected $fillable = [
        'user_id',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExportStatus::class,
            'failure' => ExportFailure::class,
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', ExportStatus::active());
    }

    public function isCompleted(): bool
    {
        return $this->status === ExportStatus::Completed;
    }

    public function fileExists(): bool
    {
        return $this->path && Storage::disk(self::DISK)->exists($this->path);
    }

    public function markProcessing(): void
    {
        $this->record(['status' => ExportStatus::Processing]);
    }

    public function markCompleted(string $path): void
    {
        $this->record(['status' => ExportStatus::Completed, 'path' => $path]);
    }

    public function markFailed(ExportFailure $failure): void
    {
        $this->record(['status' => ExportStatus::Failed, 'failure' => $failure]);
    }

    // Job-owned state, deliberately kept out of $fillable so no request can set it.
    private function record(array $attributes): void
    {
        $this->forceFill($attributes)->save();
    }

    /**
     * Generated files are only kept long enough to be downloaded.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDay());
    }

    protected function pruning(): void
    {
        if ($this->path) {
            Storage::disk(self::DISK)->delete($this->path);
        }
    }
}

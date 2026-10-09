<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chapter extends Model
{
    /** @use HasFactory<\Database\Factories\ChapterFactory> */
    use HasFactory;

    /** Worst case 4 bytes/char keeps a save under MySQL's default 1 MB max_allowed_packet. */
    public const MAX_CONTENT_LENGTH = 200_000;

    protected $fillable = [
        'title',
        'content',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /** Target name for the PDF's table-of-contents links. */
    public function pdfAnchor(): string
    {
        return "chapter-{$this->id}";
    }
}

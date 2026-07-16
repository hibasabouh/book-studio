<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Attachment extends Model
{
    protected $fillable = [
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function contactMessages(): MorphToMany
    {
        return $this->morphedByMany(ContactMessage::class, 'attachable');
    }
}

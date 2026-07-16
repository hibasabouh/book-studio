<?php

namespace App\Models;

use App\Enums\ContactCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'category',
        'message',
    ];

    protected $casts = [
        'category' => ContactCategory::class,
        'read_at' => 'datetime',
    ];

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(Attachment::class, 'attachable');
    }
}

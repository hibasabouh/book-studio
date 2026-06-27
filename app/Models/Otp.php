<?php

namespace App\Models;

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Otp extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'channel',
        'purpose',
        'target',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => OtpChannel::class,
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnused(Builder $query): void
    {
        $query->whereNull('used_at');
    }

    public function scopeNotExpired(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }

    public function scopeForPurpose(Builder $query, OtpPurpose $purpose): void
    {
        $query->where('purpose', $purpose);
    }
}

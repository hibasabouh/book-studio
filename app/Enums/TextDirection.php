<?php

namespace App\Enums;

enum TextDirection: string
{
    case Ltr = 'ltr';
    case Rtl = 'rtl';

    public function isRtl(): bool
    {
        return $this === self::Rtl;
    }

    public function label(): string
    {
        return match ($this) {
            self::Ltr => 'Left to right',
            self::Rtl => 'Right to left',
        };
    }
}

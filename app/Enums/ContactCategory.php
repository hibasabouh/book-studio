<?php

namespace App\Enums;

enum ContactCategory: string
{
    case Bug = 'bug';
    case Feature = 'feature';
    case General = 'general';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Bug => 'Bug report',
            self::Feature => 'Feature suggestion',
            self::General => 'General message',
        };
    }
}

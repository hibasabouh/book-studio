<?php

namespace App\Enums;

enum PdfTemplate: string
{
    case Classic = 'classic';
    case Modern = 'modern';
    case Elegant = 'elegant';
    case Manuscript = 'manuscript';
    case Arabic = 'arabic';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Classic',
            self::Modern => 'Modern',
            self::Elegant => 'Elegant',
            self::Manuscript => 'Manuscript',
            self::Arabic => 'Arabic',
        };
    }
}

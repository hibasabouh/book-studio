<?php

namespace App\Enums;

enum ExportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Pending, self::Processing];
    }
}

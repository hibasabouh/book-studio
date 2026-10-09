<?php

namespace App\Enums;

use Illuminate\Queue\MaxAttemptsExceededException;
use Mpdf\MpdfException;
use Throwable;

/**
 * Why a queued export failed. Stored instead of a message so it is translated
 * into the locale of whoever polls the status, not of the queue worker.
 */
enum ExportFailure: string
{
    case RenderFailed = 'render_failed';
    case TimedOut = 'timed_out';
    case Unexpected = 'unexpected';

    public static function fromException(?Throwable $exception): self
    {
        return match (true) {
            $exception instanceof MpdfException => self::RenderFailed,
            // Also covers TimeoutExceededException, which extends it.
            $exception instanceof MaxAttemptsExceededException => self::TimedOut,
            default => self::Unexpected,
        };
    }

    public function messageKey(): string
    {
        return "messages.export_failed_{$this->value}";
    }
}

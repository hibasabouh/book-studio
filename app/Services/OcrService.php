<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OcrService
{
    /**
     * Extract text from an uploaded image/PDF by calling OCR.space's
     * /parse/image endpoint directly (no SDK).
     *
     * @throws ValidationException when OCR.space fails to parse the file.
     */
    public function extractText(UploadedFile $file, ?string $language = null): string
    {
        $response = Http::withHeaders(['apikey' => config('services.ocrspace.key')])
            ->attach('file', $file->get(), $file->getClientOriginalName())
            ->asMultipart()
            ->post(config('services.ocrspace.endpoint'), [
                'language' => $language ?? 'eng',
                'OCREngine' => 2,
                'scale' => 'True',
            ]);

        $payload = $response->json();
        $result = $payload['ParsedResults'][0] ?? null;

        $isValid = is_array($payload)
            && ! ($payload['IsErroredOnProcessing'] ?? true)
            && $result !== null
            && empty($result['ErrorMessage']);

        if (! $isValid) {
            Log::warning('OCR.space extraction failed', [
                'status' => $response->status(),
                'file' => $file->getClientOriginalName(),
                'error_message' => $payload['ErrorMessage'] ?? $result['ErrorMessage'] ?? null,
                'error_details' => $payload['ErrorDetails'] ?? null,
                'body' => is_array($payload) ? null : $response->body(),
            ]);

            throw ValidationException::withMessages([
                'image' => [__('messages.ocr_failed')],
            ]);
        }

        return trim($result['ParsedText']);
    }
}

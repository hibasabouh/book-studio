<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleAuthService
{
    /**
     * Google's accepted token issuers.
     */
    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * Verify a Google ID token by calling Google's tokeninfo endpoint directly
     * (no SDK). Google validates the signature and expiry; we additionally check
     * the audience (our client ID) and issuer. Returns normalized profile claims.
     *
     * @return array{google_id: string, email: string, email_verified: bool, name: string, avatar: ?string}
     *
     * @throws ValidationException when the token is invalid or not meant for us.
     */
    public function verify(string $idToken): array
    {
        $payload = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ])->json();

        $clientId = config('services.google.client_id');

        $isValid = is_array($payload)
            && isset($payload['sub'], $payload['email'])
            && ($payload['aud'] ?? null) === $clientId
            && in_array($payload['iss'] ?? null, self::ISSUERS, true);

        if (! $isValid) {
            throw ValidationException::withMessages([
                'id_token' => ['The Google token is invalid or was issued for another app.'],
            ]);
        }

        return [
            'google_id' => $payload['sub'],
            'email' => $payload['email'],
            'email_verified' => ($payload['email_verified'] ?? false) === 'true' || ($payload['email_verified'] ?? false) === true,
            'name' => $payload['name'] ?? $payload['email'],
            'avatar' => $payload['picture'] ?? null,
        ];
    }
}

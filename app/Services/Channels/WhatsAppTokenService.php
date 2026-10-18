<?php

namespace App\Services\Channels;

use Illuminate\Support\Facades\Cache;

class WhatsAppTokenService
{
    public const TOKEN_TTL_SECONDS = 300; // 5 minutes
    public const CACHE_TTL_SECONDS = 270; // Refresh 30 seconds before expiry

    /**
     * Get or generate a valid 5-minute rotating token for inter-server communication.
     */
    public static function getToken(): string
    {
        $secret = config('services.whatsapp.secret', 'vertex_wa_secure_secret_key_2026');

        return Cache::remember('wa_server_token', self::CACHE_TTL_SECONDS, function () use ($secret) {
            return self::generateToken($secret);
        });
    }

    /**
     * Generate a new timestamped HMAC token.
     */
    public static function generateToken(?string $secret = null): string
    {
        $secret = $secret ?: config('services.whatsapp.secret', 'vertex_wa_secure_secret_key_2026');
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}:{$secret}", $secret);

        return "{$timestamp}.{$signature}";
    }

    /**
     * Verify whether a given token is valid and not expired (within 5 minutes).
     */
    public static function verifyToken(?string $token, ?string $secret = null): bool
    {
        if (empty($token) || !str_contains($token, '.')) {
            return false;
        }

        $secret = $secret ?: config('services.whatsapp.secret', 'vertex_wa_secure_secret_key_2026');
        [$timestampStr, $providedSignature] = explode('.', $token, 2);

        $timestamp = (int) $timestampStr;
        if ($timestamp <= 0) {
            return false;
        }

        // Must be within 5 minutes (300 seconds)
        if (abs(time() - $timestamp) > self::TOKEN_TTL_SECONDS) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', "{$timestamp}:{$secret}", $secret);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Build standard headers for HTTP requests to the WhatsApp microservice.
     */
    public static function getRequestHeaders(): array
    {
        $token = self::getToken();
        $secret = config('services.whatsapp.secret', 'vertex_wa_secure_secret_key_2026');

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Server-Token' => $token,
            'X-Shared-Secret' => $secret,
            'Authorization' => "Bearer {$token}",
        ];
    }
}

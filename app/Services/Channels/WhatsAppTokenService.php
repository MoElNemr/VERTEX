<?php

namespace App\Services\Channels;

use Illuminate\Support\Facades\Cache;

class WhatsAppTokenService
{
    public const TOKEN_TTL_SECONDS = 300; // 5 minutes
    public const CACHE_TTL_SECONDS = 270; // Refresh 30 seconds before expiry

    /**
     * Resolve configured WhatsApp inter-service secret.
     * In testing, fall back to a test-scoped key only if not explicitly set.
     * In production, fail closed if unconfigured.
     */
    public static function getSecret(?string $overrideSecret = null): ?string
    {
        if ($overrideSecret !== null && $overrideSecret !== '') {
            return $overrideSecret;
        }

        $secret = config('services.whatsapp.secret');
        if (!empty($secret)) {
            return $secret;
        }

        if (app()->environment('testing')) {
            return 'test_scoped_wa_secret_key';
        }

        return null;
    }

    /**
     * Get or generate a valid 5-minute rotating token for inter-server communication.
     */
    public static function getToken(?string $secret = null): string
    {
        $resolvedSecret = self::getSecret($secret);

        if (empty($resolvedSecret)) {
            throw new \RuntimeException('WHATSAPP_SERVER_SECRET is required but not configured.');
        }

        return Cache::remember('wa_server_token', self::CACHE_TTL_SECONDS, function () use ($resolvedSecret) {
            return self::generateToken($resolvedSecret);
        });
    }

    /**
     * Generate a new timestamped HMAC token.
     */
    public static function generateToken(?string $secret = null): string
    {
        $resolvedSecret = self::getSecret($secret);

        if (empty($resolvedSecret)) {
            throw new \RuntimeException('WHATSAPP_SERVER_SECRET is required but not configured.');
        }

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}:{$resolvedSecret}", $resolvedSecret);

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

        $resolvedSecret = self::getSecret($secret);
        if (empty($resolvedSecret)) {
            return false;
        }

        [$timestampStr, $providedSignature] = explode('.', $token, 2);

        $timestamp = (int) $timestampStr;
        if ($timestamp <= 0) {
            return false;
        }

        // Must be within 5 minutes (300 seconds)
        if (abs(time() - $timestamp) > self::TOKEN_TTL_SECONDS) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', "{$timestamp}:{$resolvedSecret}", $resolvedSecret);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Build standard headers for HTTP requests to the WhatsApp microservice.
     */
    public static function getRequestHeaders(): array
    {
        $token = self::getToken();
        $secret = self::getSecret();

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Server-Token' => $token,
            'X-Shared-Secret' => $secret ?? '',
            'Authorization' => "Bearer {$token}",
        ];
    }
}

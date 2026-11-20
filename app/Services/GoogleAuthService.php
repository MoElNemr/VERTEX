<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAuthService
{
    public const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    public const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public const GOOGLE_USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    public function getClientId(): ?string
    {
        return SystemSetting::get('google_client_id')
            ?: config('services.google.client_id')
            ?: env('GOOGLE_CLIENT_ID');
    }

    public function getClientSecret(): ?string
    {
        return SystemSetting::get('google_client_secret')
            ?: config('services.google.client_secret')
            ?: env('GOOGLE_CLIENT_SECRET');
    }

    public function getRedirectUri(): string
    {
        return url('/auth/google/callback');
    }

    public function isEnabled(): bool
    {
        $setting = SystemSetting::get('google_auth_enabled');
        if ($setting !== null) {
            return (bool) $setting;
        }

        return ! empty($this->getClientId());
    }

    /**
     * Build Google authorization redirect URL.
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return self::GOOGLE_AUTH_URL.'?'.$params;
    }

    /**
     * Exchange authorization code for user profile.
     *
     * @return array{id: string, email: string, name: string, avatar: ?string}|null
     */
    public function handleCallback(string $code): ?array
    {
        try {
            $response = Http::asForm()->timeout(10)->post(self::GOOGLE_TOKEN_URL, [
                'code' => $code,
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'redirect_uri' => $this->getRedirectUri(),
                'grant_type' => 'authorization_code',
            ]);

            if (! $response->successful()) {
                Log::error('Google token exchange failed: '.$response->body());

                return null;
            }

            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'] ?? null;

            if (! $accessToken) {
                return null;
            }

            $userResponse = Http::withToken($accessToken)
                ->timeout(10)
                ->get(self::GOOGLE_USERINFO_URL);

            if (! $userResponse->successful()) {
                Log::error('Google userinfo request failed: '.$userResponse->body());

                return null;
            }

            $userData = $userResponse->json();

            return [
                'id' => (string) ($userData['sub'] ?? ''),
                'email' => (string) ($userData['email'] ?? ''),
                'name' => (string) ($userData['name'] ?? ''),
                'avatar' => $userData['picture'] ?? null,
                'email_verified' => (bool) ($userData['email_verified'] ?? false),
            ];
        } catch (\Throwable $e) {
            Log::error('Google OAuth exception: '.$e->getMessage());

            return null;
        }
    }
}

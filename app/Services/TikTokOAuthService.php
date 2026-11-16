<?php

namespace App\Services;

use App\Models\SystemSetting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokOAuthService
{
    public const SCOPES = [
        'user.info.basic',
        'user.info.profile',
        'video.list',
        'comment.list',
        'comment.list.manage',
        'im.chat',
    ];

    public function getClientKey(): ?string
    {
        return SystemSetting::get('tiktok_client_key')
            ?: config('services.tiktok.client_key');
    }

    public function getClientSecret(): ?string
    {
        return SystemSetting::get('tiktok_client_secret')
            ?: config('services.tiktok.client_secret');
    }

    public function getDefaultRedirectUri(): string
    {
        return config('services.tiktok.redirect_uri') ?: route('tiktok.callback');
    }

    /**
     * @return array<int, string>
     */
    public function getScopes(): array
    {
        return self::SCOPES;
    }

    /**
     * Build TikTok OAuth v2 authorization redirect URL.
     */
    public function getAuthorizationUrl(string $state, ?string $redirectUri = null): string
    {
        $query = http_build_query([
            'client_key' => $this->getClientKey(),
            'scope' => implode(',', $this->getScopes()),
            'response_type' => 'code',
            'redirect_uri' => $redirectUri ?: $this->getDefaultRedirectUri(),
            'state' => $state,
        ]);

        return 'https://www.tiktok.com/v2/auth/authorize/?'.$query;
    }

    /**
     * Exchange authorization code for initial access and refresh tokens.
     *
     * @throws Exception
     */
    public function exchangeCodeForToken(string $code, ?string $redirectUri = null): array
    {
        $response = Http::asForm()->timeout(15)->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => $this->getClientKey(),
            'client_secret' => $this->getClientSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri ?: $this->getDefaultRedirectUri(),
        ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('message') ?? $response->json('error_description') ?? 'Failed to exchange TikTok code for token.';
            Log::error('TikTokOAuthService exchangeCodeForToken error', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new Exception($errorMsg);
        }

        $data = $response->json();
        // TikTok returns payload under 'data' or top level
        $tokenData = $data['data'] ?? $data;

        if (empty($tokenData['access_token'])) {
            throw new Exception('Invalid token payload received from TikTok: '.json_encode($data));
        }

        return $tokenData;
    }

    /**
     * Refresh an expired access token using the rotated refresh token.
     *
     * @throws Exception
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()->timeout(15)->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => $this->getClientKey(),
            'client_secret' => $this->getClientSecret(),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('message') ?? 'Failed to refresh TikTok access token.';
            Log::error('TikTokOAuthService refreshToken error', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new Exception($errorMsg);
        }

        $data = $response->json();

        return $data['data'] ?? $data;
    }

    /**
     * Fetch user info from TikTok Open API.
     *
     * @throws Exception
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->timeout(10)
            ->get('https://open.tiktokapis.com/v2/user/info/', [
                'fields' => 'open_id,union_id,avatar_url,display_name,username',
            ]);

        if (! $response->successful()) {
            Log::warning('TikTok getUserInfo failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [];
        }

        $data = $response->json();

        return $data['data']['user'] ?? $data['data'] ?? [];
    }
}

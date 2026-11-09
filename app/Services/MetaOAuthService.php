<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaOAuthService
{
    public const GRAPH_VERSION = 'v21.0';

    public const SCOPES = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_metadata',
        'pages_messaging',
        'instagram_basic',
        'instagram_manage_messages',
        'instagram_manage_comments',
        'public_profile',
    ];

    /**
     * Get configured Meta client/app ID.
     */
    public function getClientId(): ?string
    {
        return config('services.meta.client_id')
            ?? config('services.meta.app_id');
    }

    /**
     * Get configured Meta client/app secret.
     */
    public function getClientSecret(): ?string
    {
        return config('services.meta.client_secret')
            ?? config('services.meta.app_secret');
    }

    /**
     * Get default redirect URI.
     */
    public function getDefaultRedirectUri(): string
    {
        return config('services.meta.redirect_uri')
            ?: route('meta.callback');
    }

    /**
     * Get configured permission scopes.
     *
     * @return array<int, string>
     */
    public function getScopes(): array
    {
        $custom = config('services.meta.scopes');

        if (is_string($custom) && trim($custom) !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $custom))));
        }

        if (is_array($custom) && ! empty($custom)) {
            return $custom;
        }

        return self::SCOPES;
    }

    /**
     * Build the Meta OAuth dialog redirect URL.
     *
     * @param  array<int, string>|null  $scopes
     */
    public function getAuthorizationUrl(string $state, ?string $redirectUri = null, ?array $scopes = null): string
    {
        $clientId = $this->getClientId();
        $targetRedirectUri = $redirectUri ?: $this->getDefaultRedirectUri();
        $effectiveScopes = $scopes ?? $this->getScopes();

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $targetRedirectUri,
            'state' => $state,
            'scope' => implode(',', $effectiveScopes),
            'response_type' => 'code',
        ]);

        return 'https://www.facebook.com/'.self::GRAPH_VERSION.'/dialog/oauth?'.$query;
    }

    /**
     * Get configured HTTP client with SSL verification handling for local dev.
     */
    protected function httpClient(int $timeout = 15): PendingRequest
    {
        $request = Http::timeout($timeout);

        if (app()->environment('local', 'testing') && empty(ini_get('curl.cainfo'))) {
            $request->withoutVerifying();
        }

        return $request;
    }

    /**
     * Exchange authorization code for short-lived user token.
     *
     * @return array{access_token: string, token_type?: string, expires_in?: int}
     *
     * @throws Exception
     */
    public function exchangeCodeForToken(string $code, ?string $redirectUri = null): array
    {
        $targetRedirectUri = $redirectUri ?: $this->getDefaultRedirectUri();

        $response = $this->httpClient(15)->get('https://graph.facebook.com/'.self::GRAPH_VERSION.'/oauth/access_token', [
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'redirect_uri' => $targetRedirectUri,
            'code' => $code,
        ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? 'Failed to exchange authorization code for token.';
            Log::error('MetaOAuthService exchangeCodeForToken error', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new Exception($errorMsg);
        }

        return $response->json();
    }

    /**
     * Exchange short-lived user token for long-lived user token (~60 days).
     *
     * @return array{access_token: string, token_type?: string, expires_in?: int}
     *
     * @throws Exception
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $response = $this->httpClient(15)->get('https://graph.facebook.com/'.self::GRAPH_VERSION.'/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? 'Failed to upgrade to long-lived token.';
            Log::error('MetaOAuthService exchangeForLongLivedToken error', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new Exception($errorMsg);
        }

        return $response->json();
    }

    /**
     * Fetch user profile (ID & Name).
     *
     * @return array{id: string, name?: string}
     *
     * @throws Exception
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $response = $this->httpClient(10)->get('https://graph.facebook.com/'.self::GRAPH_VERSION.'/me', [
            'fields' => 'id,name',
            'access_token' => $userAccessToken,
        ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? 'Failed to retrieve Meta user profile.';
            throw new Exception($errorMsg);
        }

        return $response->json();
    }

    /**
     * Discover manageable accounts with linked Instagram business account details.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    public function getManageableAccounts(string $longLivedToken): array
    {
        $fields = 'id,name,category,access_token,picture{url},tasks,instagram_business_account{id,username,name,profile_picture_url}';

        $response = $this->httpClient(15)->get('https://graph.facebook.com/'.self::GRAPH_VERSION.'/me/accounts', [
            'fields' => $fields,
            'access_token' => $longLivedToken,
            'limit' => 100,
        ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? 'Failed to retrieve manageable Meta accounts.';
            Log::error('MetaOAuthService getManageableAccounts error', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new Exception($errorMsg);
        }

        return $response->json('data') ?? [];
    }

    /**
     * Automatically subscribe the Facebook Page to webhook topics.
     */
    public function subscribePageWebhook(string $pageId, string $pageAccessToken): bool
    {
        $response = $this->httpClient(10)->post('https://graph.facebook.com/'.self::GRAPH_VERSION."/{$pageId}/subscribed_apps", [
            'subscribed_fields' => 'messages,messaging_postbacks,message_reads,message_echoes,feed',
            'access_token' => $pageAccessToken,
        ]);

        if (! $response->successful()) {
            Log::warning("Failed to subscribe webhook for Page {$pageId}", [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        }

        return (bool) ($response->json('success') ?? true);
    }

    /**
     * Parse and verify a signed_request from Meta.
     */
    public function parseSignedRequest(string $signedRequest): ?array
    {
        if (! str_contains($signedRequest, '.')) {
            return null;
        }

        [$encodedSig, $encodedPayload] = explode('.', $signedRequest, 2);

        $secret = $this->getClientSecret();
        if (! $secret) {
            return null;
        }

        $sig = $this->base64UrlDecode($encodedSig);
        $expectedSig = hash_hmac('sha256', $encodedPayload, $secret, true);

        if (! hash_equals($sig, $expectedSig)) {
            Log::warning('Meta signed_request signature verification failed.');

            return null;
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * Helper to decode Base64-URL encoded strings.
     */
    protected function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padLen = 4 - $remainder;
            $input .= str_repeat('=', $padLen);
        }

        return base64_decode(strtr($input, '-_', '+/')) ?: '';
    }
}

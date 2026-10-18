<?php

namespace Tests\Unit;

use App\Services\Channels\WhatsAppTokenService;
use Tests\TestCase;

class WhatsAppTokenServiceTest extends TestCase
{
    public function test_can_generate_and_verify_valid_token(): void
    {
        $token = WhatsAppTokenService::generateToken('test_secret');
        $this->assertNotEmpty($token);
        $this->assertTrue(WhatsAppTokenService::verifyToken($token, 'test_secret'));
    }

    public function test_rejects_tampered_token(): void
    {
        $token = WhatsAppTokenService::generateToken('test_secret');
        $tampered = $token . 'tampered';
        $this->assertFalse(WhatsAppTokenService::verifyToken($tampered, 'test_secret'));
    }

    public function test_rejects_expired_token(): void
    {
        $oldTimestamp = time() - 305; // 5 minutes and 5 seconds ago
        $secret = 'test_secret';
        $signature = hash_hmac('sha256', "{$oldTimestamp}:{$secret}", $secret);
        $expiredToken = "{$oldTimestamp}.{$signature}";

        $this->assertFalse(WhatsAppTokenService::verifyToken($expiredToken, $secret));
    }

    public function test_rejects_wrong_secret(): void
    {
        $token = WhatsAppTokenService::generateToken('correct_secret');
        $this->assertFalse(WhatsAppTokenService::verifyToken($token, 'wrong_secret'));
    }
}

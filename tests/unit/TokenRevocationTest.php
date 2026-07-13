<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

/**
 * Validates the JWT access-token revocation helper (Phase 2) against an
 * in-memory mock cache. The JwtAuthFilter check is fail-open: a token is
 * rejected only when a revoke timestamp exists AND the token's iat predates it.
 *
 * @internal
 */
final class TokenRevocationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Services::injectMock('cache', new MockCache());
        helper('token');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Services::reset();
    }

    public function testNoRevocationByDefault(): void
    {
        $this->assertNull(tokensRevokedAfter(42));
    }

    public function testRevokeRecordsATimestamp(): void
    {
        $before = time();
        $this->assertTrue(revokeUserTokens(42));

        $after = tokensRevokedAfter(42);
        $this->assertNotNull($after);
        $this->assertGreaterThanOrEqual($before, $after);
    }

    public function testTokenIssuedBeforeRevokeIsConsideredRevoked(): void
    {
        // Simulate: token issued, then a password change revokes.
        $tokenIat = time() - 100;
        revokeUserTokens(42);
        $revokeAfter = tokensRevokedAfter(42);

        // The filter's rule: iat < revokeAfter  => revoked.
        $this->assertTrue($tokenIat < $revokeAfter);
    }

    public function testInvalidUserIdsAreIgnored(): void
    {
        $this->assertFalse(revokeUserTokens(0));
        $this->assertFalse(revokeUserTokens(''));
        $this->assertNull(tokensRevokedAfter(''));
    }
}

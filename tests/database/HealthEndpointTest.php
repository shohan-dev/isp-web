<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Exercises GET /healthz (Phase 7). The endpoint must always return a valid
 * health JSON snapshot and never 500 — 200 when the DB is reachable, 503 when
 * it is not — so this accepts either code and checks the shape.
 *
 * @internal
 */
final class HealthEndpointTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHealthzReturnsValidHealthJson(): void
    {
        $result = $this->get('healthz');

        $this->assertContains($result->response()->getStatusCode(), [200, 503]);

        $body = json_decode((string) $result->getJSON(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('status', $body);
        $this->assertArrayHasKey('checks', $body);
        $this->assertArrayHasKey('queue', $body);
        $this->assertContains($body['status'], ['ok', 'fail']);
    }
}

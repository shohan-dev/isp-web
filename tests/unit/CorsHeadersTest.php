<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Validates the same-origin CORS decision in public/cors_headers.php
 * (Phase 0.12). Pure function — no framework state needed.
 *
 * @internal
 */
final class CorsHeadersTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once FCPATH . 'cors_headers.php';
    }

    public function testSameOriginHttpsIsAllowed(): void
    {
        $allowed = cors_allowed_origin([
            'HTTP_ORIGIN' => 'https://panel.example.com',
            'HTTP_HOST'   => 'panel.example.com',
            'HTTPS'       => 'on',
        ]);
        $this->assertSame('https://panel.example.com', $allowed);
    }

    public function testSameOriginHttpIsAllowed(): void
    {
        $allowed = cors_allowed_origin([
            'HTTP_ORIGIN' => 'http://localhost:8080',
            'HTTP_HOST'   => 'localhost:8080',
        ]);
        $this->assertSame('http://localhost:8080', $allowed);
    }

    public function testCrossOriginIsRejected(): void
    {
        $allowed = cors_allowed_origin([
            'HTTP_ORIGIN' => 'https://evil.example.org',
            'HTTP_HOST'   => 'panel.example.com',
            'HTTPS'       => 'on',
        ]);
        $this->assertNull($allowed);
    }

    public function testSchemeMismatchIsRejected(): void
    {
        // http origin against an https host is a different origin -> deny.
        $allowed = cors_allowed_origin([
            'HTTP_ORIGIN' => 'http://panel.example.com',
            'HTTP_HOST'   => 'panel.example.com',
            'HTTPS'       => 'on',
        ]);
        $this->assertNull($allowed);
    }

    public function testNoOriginHeaderEmitsNothing(): void
    {
        // Native mobile client / same-origin GET with no Origin.
        $this->assertNull(cors_allowed_origin(['HTTP_HOST' => 'panel.example.com']));
    }

    public function testHostCaseIsIgnored(): void
    {
        $allowed = cors_allowed_origin([
            'HTTP_ORIGIN' => 'https://Panel.Example.com',
            'HTTP_HOST'   => 'panel.example.com',
            'HTTPS'       => 'on',
        ]);
        $this->assertSame('https://Panel.Example.com', $allowed);
    }
}

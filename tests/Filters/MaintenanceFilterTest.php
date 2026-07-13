<?php

use App\Filters\MaintenanceFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

/**
 * @internal
 */
final class MaintenanceFilterTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Services::injectMock('cache', new MockCache());
        helper('flag');
        clearFlag('maintenance_mode');
    }

    protected function tearDown(): void
    {
        clearFlag('maintenance_mode');
        parent::tearDown();
        Services::reset();
    }

    public function testOffPassesThrough(): void
    {
        $filter = new MaintenanceFilter();
        $request = $this->makeRequest('/', 'text/html');

        $this->assertNull($filter->before($request, null));
    }

    public function testOnReturns503JsonForApi(): void
    {
        setFlag('maintenance_mode', true);
        $filter = new MaintenanceFilter();
        $request = $this->makeRequest('api/reseller/dashboard', 'application/json');

        $response = $filter->before($request, null);
        $this->assertNotNull($response);
        $this->assertSame(503, $response->getStatusCode());

        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['maintenance']);
        $this->assertSame('MAINTENANCE', $body['error']['code']);
    }

    public function testHealthzExemptWhenOn(): void
    {
        setFlag('maintenance_mode', true);
        $filter = new MaintenanceFilter();
        $request = $this->makeRequest('healthz', 'text/html');

        $this->assertNull($filter->before($request, null));
    }

    public function testApiLoginExemptWhenOn(): void
    {
        setFlag('maintenance_mode', true);
        $filter = new MaintenanceFilter();
        $request = $this->makeRequest('api/common/login', 'application/json');

        $this->assertNull($filter->before($request, null));
    }

    /**
     * Regression: the exemption list previously checked 'auth/forgot' (exact)
     * and 'auth/forgot/' (prefix), but the real registered routes are
     * 'auth/forgot-password', '.../validate', '.../reset' (a dash, not a
     * slash, right after 'forgot') — so the exemption never matched and the
     * whole password-reset flow 503'd during maintenance.
     */
    public function testForgotPasswordFlowExemptWhenOn(): void
    {
        setFlag('maintenance_mode', true);
        $filter = new MaintenanceFilter();

        foreach (['auth/forgot-password', 'auth/forgot-password/validate', 'auth/forgot-password/reset'] as $path) {
            $request = $this->makeRequest($path, 'text/html');
            $this->assertNull($filter->before($request, null), "{$path} should be exempt during maintenance");
        }
    }

    private function makeRequest(string $path, string $accept): IncomingRequest
    {
        $_SERVER['REQUEST_URI'] = '/' . ltrim($path, '/');
        $_SERVER['QUERY_STRING'] = '';

        $uri = new \CodeIgniter\HTTP\URI();
        $uri->setScheme('http');
        $uri->setHost('localhost');
        $uri->setPath('/' . ltrim($path, '/'));

        $request = new IncomingRequest(
            config('App'),
            $uri,
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        $request->setHeader('Accept', $accept);

        return $request;
    }
}

<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Config\App rewrites $baseURL from the request host outside production, so that
 * `php spark serve --host 0.0.0.0 --port 5028` serves working asset/form URLs.
 *
 * @internal
 */
final class BaseUrlAutoDetectTest extends CIUnitTestCase
{
    private const CONFIGURED = 'http://localhost:5025/';

    /**
     * The regression that took the whole app down: the guard read the ENVIRONMENT
     * constant, but CodeIgniter only defines it in CodeIgniter::detectEnvironment(),
     * which runs *after* Services::codeigniter() has constructed Config\App. Every
     * entrypoint - web and spark alike - died with "Undefined constant".
     *
     * PHPUnit defines ENVIRONMENT, so no in-process test can catch this. Boot a real
     * spark command in a subprocess instead.
     */
    public function testSparkBoots(): void
    {
        $spark = ROOTPATH . 'spark';

        $process = proc_open(
            [PHP_BINARY, $spark, 'list', '--no-header'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            ROOTPATH
        );

        $this->assertIsResource($process, 'could not launch spark');

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        array_map('fclose', $pipes);
        $exit = proc_close($process);

        $this->assertSame(0, $exit, "spark failed to boot:\n{$stderr}{$stdout}");
        $this->assertStringNotContainsString('Undefined constant', $stderr . $stdout);
    }

    public function testFollowsHostAndPortTheRequestArrivedOn(): void
    {
        $this->assertSame(
            'http://192.168.0.50:5028/',
            App::resolveBaseURL(self::CONFIGURED, 'development', ['HTTP_HOST' => '192.168.0.50:5028'])
        );
    }

    public function testFollowsHostWithoutPort(): void
    {
        $this->assertSame(
            'http://isp.local/',
            App::resolveBaseURL(self::CONFIGURED, 'development', ['HTTP_HOST' => 'isp.local'])
        );
    }

    /**
     * The Host header is client-supplied. Trusting it in production hands an attacker
     * control of every absolute URL we emit (password-reset links, redirects).
     */
    public function testProductionAlwaysKeepsTheConfiguredBaseURL(): void
    {
        $this->assertSame(
            self::CONFIGURED,
            App::resolveBaseURL(self::CONFIGURED, 'production', ['HTTP_HOST' => 'evil.example.com'])
        );
    }

    /** CLI and cron have no HTTP_HOST - they must keep the configured URL. */
    public function testNoHostHeaderKeepsTheConfiguredBaseURL(): void
    {
        $this->assertSame(self::CONFIGURED, App::resolveBaseURL(self::CONFIGURED, 'development', []));
    }

    /**
     * @dataProvider provideMalformedHosts
     */
    public function testMalformedHostIsIgnored(string $host): void
    {
        $this->assertSame(
            self::CONFIGURED,
            App::resolveBaseURL(self::CONFIGURED, 'development', ['HTTP_HOST' => $host])
        );
    }

    public static function provideMalformedHosts(): iterable
    {
        yield 'injected path'   => ['localhost/../evil'];
        yield 'scheme in host'  => ['http://evil.com'];
        yield 'crlf'            => ["localhost:5028\r\nX: y"];
        yield 'userinfo'        => ['user@evil.com'];
        yield 'empty'           => [''];
        yield 'port too long'   => ['localhost:502800'];
    }

    /** A sub-directory install must keep its path segment. */
    public function testPreservesSubdirectoryPath(): void
    {
        $this->assertSame(
            'http://192.168.0.50:5028/isp/',
            App::resolveBaseURL('http://localhost:5025/isp/', 'development', ['HTTP_HOST' => '192.168.0.50:5028'])
        );
    }

    public function testDetectsHttps(): void
    {
        $this->assertSame(
            'https://isp.local/',
            App::resolveBaseURL(self::CONFIGURED, 'development', ['HTTP_HOST' => 'isp.local', 'HTTPS' => 'on'])
        );

        $this->assertSame(
            'https://isp.local/',
            App::resolveBaseURL(self::CONFIGURED, 'development', ['HTTP_HOST' => 'isp.local', 'SERVER_PORT' => 443])
        );
    }

    public function testHttpsOffIsNotTreatedAsSecure(): void
    {
        $this->assertSame(
            'http://isp.local/',
            App::resolveBaseURL(self::CONFIGURED, 'development', ['HTTP_HOST' => 'isp.local', 'HTTPS' => 'off'])
        );
    }
}

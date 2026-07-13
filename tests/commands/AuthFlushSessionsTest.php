<?php

use App\Commands\AuthFlushSessions;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Session as SessionConfig;

/**
 * auth:flush-sessions (FileHandler path) — exercised against a throwaway
 * temp directory populated with fake session files, never the real
 * writable/session directory, so no real session data is touched.
 *
 * @internal
 */
final class AuthFlushSessionsTest extends CIUnitTestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'auth_flush_sessions_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);

        foreach (['abc123', 'def456', 'ghi789'] as $sid) {
            file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . 'ci_session' . $sid, 'user_id|s:1:"1";');
        }
        // A non-session file in the same directory must survive any flush.
        file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . 'unrelated.txt', 'keep me');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tmpDir);
        parent::tearDown();
    }

    private function fakeConfig(): SessionConfig
    {
        $config = new SessionConfig();
        $config->driver = \CodeIgniter\Session\Handlers\FileHandler::class;
        $config->savePath = $this->tmpDir;
        $config->cookieName = 'ci_session';

        return $config;
    }

    private function invokeFlushFileSessions(AuthFlushSessions $cmd, SessionConfig $config, bool $dry): int
    {
        $ref = new ReflectionMethod($cmd, 'flushFileSessions');
        $ref->setAccessible(true);

        return $ref->invoke($cmd, $config, $dry);
    }

    private function command(): AuthFlushSessions
    {
        return new class extends AuthFlushSessions {
            public function __construct()
            {
            }
        };
    }

    public function testDryRunDeletesNothing(): void
    {
        $this->invokeFlushFileSessions($this->command(), $this->fakeConfig(), true);

        $remaining = glob($this->tmpDir . DIRECTORY_SEPARATOR . 'ci_session*');
        $this->assertCount(3, $remaining, 'dry-run must not delete any session file');
    }

    public function testRealRunDeletesOnlySessionFiles(): void
    {
        $this->invokeFlushFileSessions($this->command(), $this->fakeConfig(), false);

        $remainingSessions = glob($this->tmpDir . DIRECTORY_SEPARATOR . 'ci_session*');
        $this->assertCount(0, $remainingSessions, 'all session files must be deleted');
        $this->assertFileExists($this->tmpDir . DIRECTORY_SEPARATOR . 'unrelated.txt', 'non-session files must survive');
    }
}

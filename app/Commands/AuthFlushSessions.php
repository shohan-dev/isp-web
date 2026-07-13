<?php

namespace App\Commands;

use App\Libraries\UpstashRedisConfig;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Session as SessionConfig;
use Throwable;

/**
 * auth:flush-sessions — bulk-destroy every active CI4 web session (Item 1
 * role-rename cutover, PLAN.md Step 8: "destroy all CI4 web sessions"). No
 * such mechanism existed anywhere in the codebase before this command — the
 * only session-killing code path (MaintenanceFilter::forceLogoutWebSession)
 * only fires lazily, per-request, for a session that happens to make a
 * request while maintenance is ON; idle sessions were never touched.
 *
 *   php spark auth:flush-sessions --dry-run   # count session files/keys only
 *   php spark auth:flush-sessions --yes        # actually destroy every session
 *
 * Supports both session drivers this app actually uses:
 *   - FileHandler (local dev / single-node default): deletes every
 *     `{cookieName}<id>` file under the configured save path.
 *   - App\Session\Handlers\PredisHandler (prod, when redis.enabled=true):
 *     SCANs and DELs every `{prefix}*` key via the same DSN-parsing the
 *     handler itself uses (UpstashRedisConfig).
 * Any other configured driver is reported, not guessed at.
 */
class AuthFlushSessions extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:flush-sessions';
    protected $description = 'Bulk-destroy every active web session (role-rename / security cutover).';
    protected $usage       = 'auth:flush-sessions [--yes] [--dry-run]';

    public function run(array $params)
    {
        $dry = (bool) CLI::getOption('dry-run');
        $yes = (bool) CLI::getOption('yes');

        if (! $dry && ! $yes) {
            CLI::error('Refusing to run: pass --yes to actually destroy every active session '
                . '(or --dry-run to just count them). Everyone will be forced to log back in — '
                . 'only run it inside a maintenance window.');

            return EXIT_ERROR;
        }

        /** @var SessionConfig $config */
        $config = config('Session');
        $driver = $config->driver;

        CLI::write('auth:flush-sessions — ' . ($dry ? '[DRY RUN] ' : '') . "driver={$driver}", 'yellow');

        if (str_contains($driver, 'FileHandler')) {
            return $this->flushFileSessions($config, $dry);
        }

        if (str_contains($driver, 'PredisHandler')) {
            return $this->flushPredisSessions($config, $dry);
        }

        CLI::error("Unsupported session driver '{$driver}' — no flush implemented for it. "
            . 'Supported: CodeIgniter\Session\Handlers\FileHandler, App\Session\Handlers\PredisHandler.');

        return EXIT_ERROR;
    }

    private function flushFileSessions(SessionConfig $config, bool $dry): int
    {
        $path = rtrim($config->savePath, '/\\');
        if (! is_dir($path)) {
            CLI::error("Session save path not found: {$path}");

            return EXIT_ERROR;
        }

        $prefix = $config->cookieName;
        $files  = glob($path . DIRECTORY_SEPARATOR . $prefix . '*') ?: [];

        CLI::write('  path=' . $path . " prefix={$prefix} — " . count($files) . ' session file(s) found', 'dark_gray');

        if ($dry) {
            CLI::write('Would delete ' . count($files) . ' session file(s). Re-run with --yes to apply.', 'cyan');

            return EXIT_SUCCESS;
        }

        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file) && @unlink($file)) {
                $deleted++;
            }
        }

        CLI::write("Deleted {$deleted}/" . count($files) . ' session file(s).', 'green');

        return EXIT_SUCCESS;
    }

    private function flushPredisSessions(SessionConfig $config, bool $dry): int
    {
        try {
            $parsed = UpstashRedisConfig::parseSessionSavePath((string) $config->savePath);
            $redis  = UpstashRedisConfig::createPredisClient($parsed);
        } catch (Throwable $e) {
            CLI::error('Could not connect to Redis/Predis: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        $prefix = $parsed['prefix'];
        CLI::write("  host={$parsed['host']} port={$parsed['port']} db={$parsed['database']} prefix={$prefix}", 'dark_gray');

        $cursor = 0;
        $keys   = [];

        do {
            $result = $redis->scan($cursor, ['match' => $prefix . '*', 'count' => 500]);
            if ($result === false || ! is_array($result)) {
                CLI::error('SCAN failed — Redis unreachable or command not supported.');

                return EXIT_ERROR;
            }
            [$cursor, $batchKeys] = $result;
            $keys = array_merge($keys, $batchKeys);
            $cursor = (int) $cursor;
        } while ($cursor !== 0);

        $keys = array_unique($keys);
        CLI::write('  ' . count($keys) . ' session key(s) found', 'dark_gray');

        if ($dry) {
            CLI::write('Would delete ' . count($keys) . ' session key(s). Re-run with --yes to apply.', 'cyan');

            return EXIT_SUCCESS;
        }

        $deleted = 0;
        foreach (array_chunk($keys, 500) as $chunk) {
            $deleted += (int) $redis->del($chunk);
        }

        CLI::write("Deleted {$deleted}/" . count($keys) . ' session key(s).', 'green');

        return EXIT_SUCCESS;
    }
}

<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

/**
 * auth:revoke-all-tokens — bulk-stamp every user's jwt_revoke_after cache key
 * (Item 1 role-rename cutover, PLAN.md Step 8: "global JWT revoke — bump
 * revokeUserTokens() stamp for every user"). This step had no implementation
 * anywhere in the codebase before this command — revokeUserTokens() only
 * ever gets called for a single user, from the password-change and
 * password-reset flows. This closes that gap so the cutover's stated
 * acceptance criterion ("every old JWT ... is invalid" after cutover) is
 * actually achievable with a single command instead of a hand-written script.
 *
 *   php spark auth:revoke-all-tokens --dry-run   # count users only, no writes
 *   php spark auth:revoke-all-tokens --yes        # actually revoke everyone
 *
 * Safe by construction:
 *   - Only ever writes cache keys (jwt_revoke_after_<id>) — never touches the
 *     users table or any other persisted data. Fully reversible: entries
 *     expire on their own TTL, or a fresh login simply reissues valid tokens.
 *   - Paginates the users table in bounded batches — never loads all rows.
 *   - Refuses to run without --yes (this immediately forces EVERY active
 *     session/token holder to re-login — only run it inside the maintenance
 *     window described in PLAN.md Item 1 Step 8).
 */
class AuthRevokeAllTokens extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:revoke-all-tokens';
    protected $description = 'Bulk-revoke every user\'s JWT tokens (role-rename / security cutover).';
    protected $usage       = 'auth:revoke-all-tokens [--yes] [--dry-run] [--batch=1000]';

    public function run(array $params)
    {
        $dry   = (bool) CLI::getOption('dry-run');
        $yes   = (bool) CLI::getOption('yes');
        $batch = max(100, (int) (CLI::getOption('batch') ?? 1000));

        if (! $dry && ! $yes) {
            CLI::error('Refusing to run: pass --yes to actually revoke every user\'s tokens '
                . '(or --dry-run to just count them). This forces every active session to re-login — '
                . 'only run it inside a maintenance window.');

            return EXIT_ERROR;
        }

        try {
            $db = $this->connection();
            $db->initialize();
        } catch (Throwable $e) {
            CLI::error('Could not connect to the database: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        return $this->execute($db, $dry, $batch);
    }

    /** Overridable seam for tests — production path is the default connection group. */
    protected function connection()
    {
        return Database::connect();
    }

    protected function execute($db, bool $dry, int $batch): int
    {
        if (! $db->tableExists('users')) {
            CLI::error('users table not found.');

            return EXIT_ERROR;
        }

        helper('token');

        $total = (int) $db->table('users')->countAllResults();
        CLI::write('auth:revoke-all-tokens — ' . ($dry ? '[DRY RUN] ' : '') . "{$total} user(s) total, batch {$batch}", 'yellow');
        CLI::newLine();

        $processed = 0;
        $failed    = 0;
        $offset    = 0;

        while (true) {
            $rows = $db->table('users')->select('id')->orderBy('id', 'ASC')->limit($batch, $offset)->get()->getResultArray();
            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $id = (int) $row['id'];
                if ($dry) {
                    $processed++;
                    continue;
                }

                if (revokeUserTokens($id)) {
                    $processed++;
                } else {
                    $failed++;
                }
            }

            CLI::write("  … {$processed}/{$total} processed", 'dark_gray');
            $offset += $batch;
        }

        CLI::newLine();
        if ($dry) {
            CLI::write("Would revoke tokens for {$processed} user(s). Re-run with --yes to apply.", 'cyan');
        } else {
            CLI::write("Revoked tokens for {$processed} user(s)." . ($failed > 0 ? " {$failed} failed (cache write error — fails open, not fatal)." : ''), 'green');
        }

        return EXIT_SUCCESS;
    }
}

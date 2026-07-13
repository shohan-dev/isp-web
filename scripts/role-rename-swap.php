<?php
/**
 * One-shot collision-safe role literal swap for Item 1.
 * Run from isp-core/: php scripts/role-rename-swap.php
 */
declare(strict_types=1);

const TMP = '__SA_TMP__';

$root = dirname(__DIR__);
$dirs = [$root . '/app', $root . '/zapi', $root . '/tests'];

$skipFiles = [
    'app/Config/Roles.php',
    'app/Enums/UserRole.php',
    'app/Database/Migrations/2026-07-07-000003_RenameRoleValues.php',
    'app/Filters/RoleGuard.php',
    'tests/unit/EnumsTest.php',
];

$files = [];
foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        if (in_array($rel, $skipFiles, true)) {
            continue;
        }
        $files[] = $file->getPathname();
    }
}

$changed = 0;
foreach ($files as $path) {
    $original = file_get_contents($path);
    if ($original === false) {
        continue;
    }

    $content = $original;
    $lines = explode("\n", $content);
    $out = [];

    foreach ($lines as $line) {
        $out[] = transformLine($line, $path);
    }

    $newContent = implode("\n", $out);
    if ($newContent !== $original) {
        file_put_contents($path, $newContent);
        $changed++;
        echo "Updated: " . str_replace('\\', '/', substr($path, strlen($root) + 1)) . "\n";
    }
}

echo "Done. {$changed} files updated.\n";

function transformLine(string $line, string $path): string
{
    if (shouldSkipLine($line, $path)) {
        return $line;
    }

    // Phase A: role-context 'admin' / "admin" → TMP
    $line = preg_replace("/(?<![a-zA-Z0-9_])'admin'(?![a-zA-Z0-9_])/", "'" . TMP . "'", $line) ?? $line;
    $line = preg_replace('/(?<![a-zA-Z0-9_])"admin"(?![a-zA-Z0-9_])/', '"' . TMP . '"', $line) ?? $line;

    // Phase B: sAdmin → admin
    $line = str_replace("'sAdmin'", "'admin'", $line);
    $line = str_replace('"sAdmin"', '"admin"', $line);

    // Phase C: TMP → super_admin
    $line = str_replace("'" . TMP . "'", "'super_admin'", $line);
    $line = str_replace('"' . TMP . '"', '"super_admin"', $line);

    if (shouldSkipLowercaseLine($line, $path)) {
        return $line;
    }

    // Lowercase sweep: sadmin → admin (platform lowercase admin handled in phase A–C).
    $line = preg_replace("/(?<![a-zA-Z0-9_])'sadmin'(?![a-zA-Z0-9_])/", "'admin'", $line) ?? $line;
    $line = preg_replace('/(?<![a-zA-Z0-9_])"sadmin"(?![a-zA-Z0-9_])/', '"admin"', $line) ?? $line;

    return $line;
}

function shouldSkipLine(string $line, string $path): bool
{
    $rel = str_replace('\\', '/', $path);
    if (str_ends_with($rel, 'app/Models/TenantModel.php') && str_contains($line, "'www'")) {
        return true;
    }

    $needles = [
        "post('admin'",
        'post("admin"',
        "group('admins'",
        'value="admin"',
        "transferMode === 'admin'",
        'transfer_to_admin',
        'isAdminTransfer',
        "'admin' =>",
        '"admin" =>',
        'data-ipb-dashboard="admin"',
        'placeholder="admin"',
        'Admin::',
        'extends Admin',
        'class Admin',
        'processAdminPayment',
        'calsAdminPackageExpireDate',
        'getSAdminIdForUser',
        'assertSAdminCanAddCustomer',
        'assertTenantCanAddCustomer',
        'opensAdmin',
        'route.Admin',
        'SecondAdmin',
        'sAdmin.php',
        'dashboard/admin',
        'user_type\' => \'reseller',
    ];

    foreach ($needles as $needle) {
        if (str_contains($line, $needle)) {
            return true;
        }
    }

    return false;
}

function shouldSkipLowercaseLine(string $line, string $path): bool
{
    return shouldSkipLine($line, $path);
}

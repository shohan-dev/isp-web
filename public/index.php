<?php

// Increase memory limit for production
ini_set('memory_limit', '512M');

// CORS: same-origin lock (Phase 0.12). Replaces the previous wildcard
// `Access-Control-Allow-Origin: *`, which let any site read API responses.
// Guarded require: if the helper is ever deployed without this sibling file,
// we emit NO CORS headers (strictest — same-origin still works) rather than 500.
$corsLib = __DIR__ . DIRECTORY_SEPARATOR . 'cors_headers.php';
if (is_file($corsLib)) {
    require $corsLib;
    $allowedOrigin = cors_allowed_origin($_SERVER);
    if ($allowedOrigin !== null) {
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
}

// Check PHP version.
$minPhpVersion = '7.4'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION
    );

    exit($message);
}

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
chdir(FCPATH);

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 */

/**
 * PATH CONFIGURATION
 * 
 * On cPanel, the core files are often in a folder like 'isp-core' 
 * sibling to 'public_html'. This logic attempts to find the 
 * Paths.php file in standard locations.
 */

$pathsPath = FCPATH . '../app/Config/Paths.php'; // Local/Default

// Check if sibling 'isp-core' folder exists (Typical cPanel structure)
if (!file_exists($pathsPath)) {
    if (file_exists(FCPATH . '../isp-core/app/Config/Paths.php')) {
        $pathsPath = FCPATH . '../isp-core/app/Config/Paths.php';
    }
}

// Final check
if (!file_exists($pathsPath)) {
    header('HTTP/1.1 500 Internal Server Error');
    exit("Config error: The 'app/Config/Paths.php' file was not found. Please check your folder structure in index.php. Tried: " . $pathsPath);
}

require $pathsPath;

$paths = new Config\Paths();

// Location of the framework bootstrap file.
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Load environment settings from .env files into $_SERVER and $_ENV
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

/*
 * ---------------------------------------------------------------
 * GRAB OUR CODEIGNITER INSTANCE
 * ---------------------------------------------------------------
 */

$app = Config\Services::codeigniter();
$app->initialize();
$context = is_cli() ? 'php-cli' : 'web';
$app->setContext($context);

/*
 *---------------------------------------------------------------
 * LAUNCH THE APPLICATION
 *---------------------------------------------------------------
 */

$app->run();

<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * VERCEL ENTRY POINT
 *---------------------------------------------------------------
 * This mirrors public/index.php, the normal CodeIgniter front
 * controller. It exists separately because Vercel's PHP runtime
 * (vercel-community/php) expects the handler under /api, while
 * CI4 still needs FCPATH to point at the real /public folder so
 * that relative asset paths and file lookups keep working.
 */

$minPhpVersion = '8.2';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

// Point FCPATH at the real public/ folder, not this api/ folder.
define('FCPATH', realpath(__DIR__ . '/../public') . DIRECTORY_SEPARATOR);

if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

require FCPATH . '../app/Config/Paths.php';

$paths = new Paths();

require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));

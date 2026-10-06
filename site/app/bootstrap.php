<?php
declare(strict_types=1);

// +1 by RDNA website. Shared start-up for every page.

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
date_default_timezone_set('Africa/Cairo');
mb_internal_encoding('UTF-8');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/marks.data.php';
require_once __DIR__ . '/options.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/migrations.php';

$GLOBALS['PLUSONE_CONFIG'] = is_file(APP_ROOT . '/config.php') ? require APP_ROOT . '/config.php' : null;

function config(): ?array
{
    return $GLOBALS['PLUSONE_CONFIG'];
}

function installed(): bool
{
    return is_array(config());
}

if (installed()) {
    migrate();
}

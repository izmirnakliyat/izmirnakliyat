<?php
declare(strict_types=1);

/**
 * PHPUnit bootstrap: autoloader + minimal ortam hazirlik.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Test ortami icin PROJECT_ROOT tanimla
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', realpath(__DIR__ . '/..'));
}

// Zaman dilimi (production ile ayni)
date_default_timezone_set('Europe/Istanbul');

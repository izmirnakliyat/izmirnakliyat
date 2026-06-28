<?php
/**
 * Ortak .env okuyucu — config.php ve robots.php (session yok) tarafından paylaşılır.
 */
declare(strict_types=1);

if (!function_exists('mynak_env_str')) {
    function mynak_env_str($key)
    {
        $key = (string) $key;
        $g = getenv($key);
        if ($g !== false) {
            return (string) $g;
        }
        return isset($_ENV[$key]) ? (string) $_ENV[$key] : '';
    }
}
if (!function_exists('mynak_load_dotenv')) {
    function mynak_load_dotenv($path)
    {
        static $loadedPaths = [];
        $path = (string) $path;
        if (isset($loadedPaths[$path]) || !is_readable($path)) {
            return;
        }
        $loadedPaths[$path] = true;
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }
        if (isset($lines[0]) && strncmp($lines[0], "\xEF\xBB\xBF", 3) === 0) {
            $lines[0] = substr($lines[0], 3);
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $k = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));
            if ($k === '') {
                continue;
            }
            $len = strlen($val);
            if ($len >= 2 && (($val[0] === '"' && $val[$len - 1] === '"') || ($val[0] === "'" && $val[$len - 1] === "'"))) {
                $val = substr($val, 1, -1);
            }
            $_ENV[$k] = $val;
            if (function_exists('putenv')) {
                @putenv($k . '=' . $val);
            }
        }
    }
}
if (is_readable(__DIR__ . '/mynak_env_bootstrap.php')) {
    require_once __DIR__ . '/mynak_env_bootstrap.php';
}

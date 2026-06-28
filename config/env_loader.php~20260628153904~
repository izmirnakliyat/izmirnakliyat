<?php
/**
 * Basit .env yükleyici (Composer yok; üretimde DB sırları db.php içinde tutulmaz).
 * config.php zaten mynak_* polyfill tanımlayabilir; çift tanım fatal olmasın diye guard.
 */
declare(strict_types=1);

if (!function_exists('mynak_load_dotenv')) {
    function mynak_load_dotenv(string $path): void
    {
        static $loadedPaths = [];
        if (isset($loadedPaths[$path])) {
            return;
        }
        if (!is_readable($path)) {
            return;
        }
        $loadedPaths[$path] = true;

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        if (isset($lines[0]) && str_starts_with($lines[0], "\xEF\xBB\xBF")) {
            $lines[0] = substr($lines[0], 3);
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $key = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));
            if ($key === '') {
                continue;
            }
            if (
                (str_starts_with($val, '"') && str_ends_with($val, '"'))
                || (str_starts_with($val, "'") && str_ends_with($val, "'"))
            ) {
                $val = substr($val, 1, -1);
            }
            $_ENV[$key] = $val;
            if (function_exists('putenv')) {
                @putenv($key . '=' . $val);
            }
        }
    }
}

if (!function_exists('mynak_env_str')) {
    /**
     * Canlıda bazı barındırıcılarda putenv/getenv çalışmaz; $_ENV yedek okuma.
     */
    function mynak_env_str(string $key): string
    {
        $g = getenv($key);
        if ($g !== false) {
            return (string) $g;
        }
        return (string) ($_ENV[$key] ?? '');
    }
}

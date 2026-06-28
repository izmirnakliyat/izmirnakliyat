<?php
declare(strict_types=1);

/**
 * bootstrap.php ob_start geri çağrısı: HTTP çıktı tamponunu işler.
 * Varsayılan davranış tamponu olduğu gibi iletmektir (şimdilik no-op).
 *
 * @see https://www.php.net/manual/en/function.ob-start.php
 */
function mynak_http_output_buffer_handler(string $buffer, int $phase): string
{
    if (!(($phase & PHP_OUTPUT_HANDLER_FINAL) === PHP_OUTPUT_HANDLER_FINAL) || $buffer === ''
        || PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        return $buffer;
    }
    if (!str_contains($buffer, 'xampp') && !str_contains($buffer, '/C:') && stripos($buffer, '/C%3A') === false) {
        return $buffer;
    }
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return $buffer;
    }
    $hq = preg_quote($host, '#');
    // /C:/… ve /mynakliyat/C:/… (ön ekli) — href / Location sızıntısı
    $buffer = preg_replace(
        '#(https?://' . $hq . ')(?:(?:/[^/"\'<>\s?#]+)+)?/C(?::|%3A)(?:/[^/"\'<>\s?#]*?)/(?:htdocs|www)/([^/"\']+)(/[^"\'<>\s]*)?#i',
        '$1/$2$3',
        $buffer
    );

    return preg_replace(
        '#(https?://' . $hq . ')/C(?::|%3A)(?:/[^"\'>\s]*?)/(?:htdocs|www)/([^/"\']+)(/[^"\'>\s]*)?#i',
        '$1/$2$3',
        $buffer
    );
}

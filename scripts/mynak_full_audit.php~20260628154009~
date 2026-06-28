<?php
declare(strict_types=1);

/**
 * Tam HTTP / üretim uyumu denetimi.
 *
 * --mode=strict|normal
 * --target=http://host/path
 * --check=all|http|html|headers|special|none
 * --fail-on=critical|warning|all
 * --compare=php-cli-vs-http
 * --audit-env=/audit_env
 * --normalize=bom,utf8,headers
 * --verify-first-byte
 * --verify-display-errors
 * --verify-sapi-drift
 */

/**
 * @return array{level:string,check:string,message:string,url?:string}
 */
function mynak_audit_add(array &$findings, string $level, string $check, string $message, string $url = ''): void
{
    $findings[] = [
        'level' => $level,
        'check' => $check,
        'message' => $message,
        'url' => $url,
    ];
}

/**
 * @return array<string, mixed>
 */
function mynak_audit_default_opts(): array
{
    return [
        'mode' => 'normal',
        'target' => rtrim(getenv('MYNAK_AUDIT_TARGET') ?: 'http://127.0.0.1/mynakliyat', '/'),
        'check' => 'all',
        'fail_on' => 'critical',
        'compare' => '',
        'audit_env' => '/audit_env',
        'normalize' => ['bom' => false, 'utf8' => false, 'headers' => false],
        'verify_first_byte' => false,
        'verify_display_errors' => false,
        'verify_sapi_drift' => false,
    ];
}

/**
 * @param list<string> $argv
 * @return array<string, mixed>
 */
function mynak_audit_parse_args(array $argv): array
{
    $out = mynak_audit_default_opts();
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '' || $arg[0] !== '-') {
            continue;
        }
        if ($arg === '--verify-first-byte') {
            $out['verify_first_byte'] = true;
            continue;
        }
        if ($arg === '--verify-display-errors') {
            $out['verify_display_errors'] = true;
            continue;
        }
        if ($arg === '--verify-sapi-drift') {
            $out['verify_sapi_drift'] = true;
            continue;
        }
        $eq = strpos($arg, '=');
        if ($eq === false) {
            continue;
        }
        $key = substr($arg, 2, $eq - 2);
        $val = substr($arg, $eq + 1);
        switch ($key) {
            case 'mode':
                $out['mode'] = $val === 'strict' ? 'strict' : 'normal';
                break;
            case 'target':
                $out['target'] = rtrim($val, '/');
                break;
            case 'check':
                $out['check'] = $val;
                break;
            case 'fail-on':
            case 'fail_on':
                $v = strtolower($val);
                $out['fail_on'] = in_array($v, ['critical', 'warning', 'all'], true) ? $v : 'critical';
                break;
            case 'compare':
                $out['compare'] = $val;
                break;
            case 'audit-env':
            case 'audit_env':
                $path = '/' . ltrim($val, '/');
                $out['audit_env'] = $path === '//' ? '/audit_env' : $path;
                break;
            case 'normalize':
                $out['normalize'] = ['bom' => false, 'utf8' => false, 'headers' => false];
                foreach (array_map('trim', explode(',', $val)) as $token) {
                    $t = strtolower($token);
                    if ($t === 'bom') {
                        $out['normalize']['bom'] = true;
                    }
                    if ($t === 'utf8') {
                        $out['normalize']['utf8'] = true;
                    }
                    if ($t === 'headers') {
                        $out['normalize']['headers'] = true;
                    }
                }
                break;
        }
    }
    return $out;
}

function mynak_audit_display_errors_effectively_on(string $raw): bool
{
    $v = strtolower(trim($raw));
    if ($v === '1' || $v === 'on' || $v === 'true') {
        return true;
    }
    if (str_contains($v, 'stdout') || str_contains($v, 'stderr')) {
        return true;
    }
    return false;
}

/**
 * Gövde ve başlık normalleştirme (denetim öncesi).
 *
 * @param array{bom:bool,utf8:bool,headers:bool} $normalize
 * @return array{body:string,headers:string}
 */
function mynak_audit_apply_normalize(string $body, string $headers, array $normalize, bool $isHtml, array &$findings, string $url): array
{
    if (!empty($normalize['bom'])) {
        $before = $body;
        $body = ltrim($body, "\xEF\xBB\xBF");
        if ($before !== $body && $before !== '') {
            mynak_audit_add($findings, 'info', 'normalize', 'UTF-8 BOM gövdeden kaldırıldı', $url);
        }
    }
    $body = ltrim($body, " \t\r\n");

    if (!empty($normalize['utf8']) && $isHtml && $body !== '') {
        if (function_exists('mb_check_encoding') && !mb_check_encoding($body, 'UTF-8')) {
            mynak_audit_add($findings, 'warning', 'normalize', 'Gövde geçerli UTF-8 olarak doğrulanamadı', $url);
        }
    }

    if (!empty($normalize['headers']) && $isHtml && $headers !== '') {
        if (preg_match('/^content-type:\s*.+$/mi', $headers, $m) === 1) {
            $line = $m[0];
            if (!preg_match('/charset\s*=\s*utf-8/i', $line)) {
                mynak_audit_add($findings, 'warning', 'normalize', 'Content-Type satırında charset=utf-8 beklenir', $url);
            }
        }
    }

    return ['body' => $body, 'headers' => $headers];
}

/**
 * @return array{sapi:string,display_errors:string,log_errors:string,error_reporting:int,php_ini_loaded_file:string}|null
 */
function mynak_audit_cli_snapshot_after_bootstrap(string $projectRoot): ?array
{
    $cwd = getcwd();
    if (!is_dir($projectRoot)) {
        return null;
    }
    chdir($projectRoot);
    try {
        if (!defined('MYNAK_BOOTSTRAP_LOADED')) {
            require_once $projectRoot . '/bootstrap.php';
        }
        if (!defined('SITE_URL')) {
            require_once $projectRoot . '/config/config.php';
        }
        return [
            'sapi' => PHP_SAPI,
            'display_errors' => (string) ini_get('display_errors'),
            'log_errors' => (string) ini_get('log_errors'),
            'error_reporting' => error_reporting(),
            'php_ini_loaded_file' => (string) (php_ini_loaded_file() ?: ''),
        ];
    } finally {
        if ($cwd !== false && $cwd !== '') {
            chdir($cwd);
        }
    }
}

/**
 * @return array<string, mixed>|null
 */
function mynak_audit_http_env_json(string $url, ?string $secret): ?array
{
    $ch = curl_init($url);
    if ($ch === false) {
        return null;
    }
    $headers = ['Accept: application/json'];
    if (is_string($secret) && $secret !== '') {
        $headers[] = 'X-Mynak-Audit-Secret: ' . $secret;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $code !== 200) {
        return null;
    }
    $body = ltrim((string) $body, "\xEF\xBB\xBF \t\r\n");
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

/**
 * @param array<string, mixed>|null $http
 */
function mynak_audit_compare_cli_http(
    array &$findings,
    string $target,
    ?array $cli,
    ?array $http,
    string $mode,
    string $auditEnvPath,
    bool $doFullCompare,
    bool $verifyDisplayErrors,
    bool $verifySapiDrift
): void {
    $strict = $mode === 'strict';
    $envUrl = $target . $auditEnvPath;

    if ($cli === null) {
        mynak_audit_add($findings, 'critical', 'php_cli_vs_http', 'CLI snapshot alınamadı (proje kökü?)', $target);

        return;
    }
    if ($http === null) {
        mynak_audit_add($findings, 'critical', 'php_cli_vs_http', "HTTP probe yanıtı okunamadı (200 JSON; 127.0.0.1; örn. {$auditEnvPath})", $envUrl);

        return;
    }

    $cliDe = (string) ($cli['display_errors'] ?? '');
    $httpDe = (string) ($http['display_errors'] ?? '');
    $cliSapi = (string) ($cli['sapi'] ?? '');
    $httpSapi = (string) ($http['sapi'] ?? '');
    $cliIni = (string) ($cli['php_ini_loaded_file'] ?? '');
    $httpIni = (string) ($http['php_ini_loaded_file'] ?? '');

    if ($doFullCompare) {
        mynak_audit_add($findings, 'info', 'php_cli_vs_http', "CLI SAPI={$cliSapi} display_errors={$cliDe} error_reporting=" . (string) ($cli['error_reporting'] ?? ''), $target);
        mynak_audit_add($findings, 'info', 'php_cli_vs_http', "HTTP SAPI={$httpSapi} display_errors={$httpDe} error_reporting=" . (string) ($http['error_reporting'] ?? ''), $envUrl);
    }

    if (($doFullCompare || $verifySapiDrift) && $cliSapi !== $httpSapi) {
        mynak_audit_add($findings, 'info', 'php_cli_vs_http', 'SAPI farkı beklenen (cli vs apache/fpm)', '');
    }

    if (($verifyDisplayErrors || $doFullCompare) && mynak_audit_display_errors_effectively_on($httpDe)) {
        mynak_audit_add($findings, 'critical', 'verify_display_errors', 'HTTP display_errors açık (üretimde kapalı olmalı)', $envUrl);
    } elseif ($strict && ($verifyDisplayErrors || $doFullCompare) && mynak_audit_display_errors_effectively_on($cliDe)) {
        mynak_audit_add($findings, 'warning', 'verify_display_errors', 'CLI snapshot display_errors açık (bootstrap sonrası beklenmez)', '');
    }

    if ($doFullCompare && $cliDe !== $httpDe) {
        mynak_audit_add($findings, 'warning', 'php_cli_vs_http', "display_errors dizgesi farklı: CLI={$cliDe} HTTP={$httpDe}", '');
    }

    $cliEr = (int) ($cli['error_reporting'] ?? 0);
    $httpEr = (int) ($http['error_reporting'] ?? 0);
    if ($doFullCompare && $cliEr !== $httpEr) {
        mynak_audit_add($findings, 'warning', 'php_cli_vs_http', "error_reporting farklı: CLI={$cliEr} HTTP={$httpEr}", '');
    }

    if ($verifySapiDrift) {
        if ($httpSapi === 'cli') {
            mynak_audit_add($findings, 'warning', 'verify_sapi_drift', 'HTTP yanıtı SAPI=cli (beklenmez)', $envUrl);
        }
        $allowedHttp = ['apache2handler', 'fpm-fcgi', 'cgi-fcgi', 'litespeed', 'frankenphp'];
        if ($httpSapi !== '' && !in_array($httpSapi, $allowedHttp, true) && $httpSapi !== 'cli') {
            mynak_audit_add($findings, 'info', 'verify_sapi_drift', "HTTP SAPI listeye dahil değil: {$httpSapi}", $envUrl);
        }
        if ($cliIni !== $httpIni && $cliIni !== '' && $httpIni !== '') {
            mynak_audit_add($findings, 'warning', 'verify_sapi_drift', 'php_ini_loaded_file CLI vs HTTP farklı (ortam kayması olabilir)', $envUrl);
        }
        if ($cliEr !== $httpEr) {
            mynak_audit_add($findings, 'warning', 'verify_sapi_drift', "error_reporting drift: CLI={$cliEr} HTTP={$httpEr}", $envUrl);
        }
        if ($cliDe !== $httpDe) {
            mynak_audit_add($findings, 'warning', 'verify_sapi_drift', "display_errors drift: CLI={$cliDe} HTTP={$httpDe}", $envUrl);
        }
    }

    if ($doFullCompare) {
        $cliLog = (string) ($cli['log_errors'] ?? '');
        $httpLog = (string) ($http['log_errors'] ?? '');
        if ($cliLog !== $httpLog) {
            mynak_audit_add($findings, 'info', 'php_cli_vs_http', "log_errors CLI={$cliLog} HTTP={$httpLog}", '');
        }
    }
}

/**
 * @return array{code:int,headers:string,body:string}
 */
function mynak_audit_fetch(string $url, bool $headOnly = false): array
{
    $ch = curl_init($url);
    if ($ch === false) {
        return ['code' => 0, 'headers' => '', 'body' => ''];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_NOBODY => $headOnly,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    if ($raw === false) {
        return ['code' => $code, 'headers' => '', 'body' => ''];
    }
    return [
        'code' => $code,
        'headers' => substr($raw, 0, $headerSize),
        'body' => $headOnly ? '' : substr($raw, $headerSize),
    ];
}

function mynak_audit_header_has(string $headers, string $name): bool
{
    return preg_match('/^' . preg_quote($name, '/') . ':\s*.+$/mi', $headers) === 1;
}

function mynak_audit_php_noise_in_html(string $body, bool $strict): bool
{
    if (preg_match('#<br\s*/?>\s*<b>(Warning|Notice|Deprecated|Fatal error)</b>#i', $body) === 1) {
        return true;
    }
    if (preg_match('#<b>Fatal error</b>#i', $body) === 1) {
        return true;
    }
    if ($strict && preg_match('#^(?:\s*<br\s*/?>\s*)*(?:Warning|Notice|Deprecated|Fatal error)\s*:\s*.+#mi', $body) === 1) {
        return true;
    }
    return false;
}

/**
 * @param array{bom:bool,utf8:bool,headers:bool} $normalize
 */
function mynak_audit_run(string $base, string $mode, string $check, array &$findings, array $normalize, bool $verifyFirstByte): void
{
    $strict = $mode === 'strict';
    $doAll = $check === 'all';
    $doHttp = $doAll || $check === 'http';
    $doHtml = $doAll || $check === 'html';
    $doHeaders = $doAll || $check === 'headers';
    $doSpecial = $doAll || $check === 'special';

    $htmlEndpoints = [
        ['path' => '/', 'html' => true],
        ['path' => '/blog', 'html' => true],
        ['path' => '/iletisim', 'html' => true],
    ];
    $specialEndpoints = [
        ['path' => '/robots.txt', 'html' => false],
        ['path' => '/sitemap-index.xml', 'html' => false],
        ['path' => '/sitemap.xml', 'html' => false],
        ['path' => '/llms.txt', 'html' => false],
    ];

    foreach ($htmlEndpoints as $ep) {
        if (!$doHttp && !$doHtml && !$doHeaders) {
            break;
        }
        $url = $base . $ep['path'];
        $res = mynak_audit_fetch($url, false);
        if ($res['code'] >= 500) {
            mynak_audit_add($findings, 'critical', 'http_status', "HTTP {$res['code']} (server error)", $url);
        } elseif ($res['code'] < 200 || $res['code'] >= 400) {
            mynak_audit_add($findings, 'critical', 'http_status', "HTTP {$res['code']} (expected 2xx)", $url);
        } else {
            mynak_audit_add($findings, 'info', 'http_status', "HTTP {$res['code']}", $url);
        }

        if ($doHeaders && $ep['html']) {
            if ($strict) {
                if (!mynak_audit_header_has($res['headers'], 'X-Frame-Options')) {
                    mynak_audit_add($findings, 'warning', 'security_headers', 'Missing X-Frame-Options', $url);
                }
                if (!mynak_audit_header_has($res['headers'], 'X-Content-Type-Options')) {
                    mynak_audit_add($findings, 'warning', 'security_headers', 'Missing X-Content-Type-Options', $url);
                }
            }
        }

        if ($doHtml && $ep['html'] && $res['code'] >= 200 && $res['code'] < 400) {
            $norm = mynak_audit_apply_normalize($res['body'], $res['headers'], $normalize, true, $findings, $url);
            $body = $norm['body'];
            $hdrs = $norm['headers'];

            if ($verifyFirstByte) {
                if ($body === '' || strtolower($body[0]) !== '<') {
                    mynak_audit_add($findings, 'critical', 'verify_first_byte', 'İlk anlamlı bayt < değil (normalize sonrası)', $url);
                }
            }

            $head = strtolower(substr($body, 0, 20));
            if (!str_starts_with($head, '<!doctype html')) {
                mynak_audit_add($findings, 'critical', 'html_doctype', 'İlk çıktı <!doctype html değil (got: ' . json_encode(substr($body, 0, 80)) . ')', $url);
            }
            if (mynak_audit_php_noise_in_html($body, $strict)) {
                mynak_audit_add($findings, 'critical', 'php_leak', 'PHP warning/notice/fatal pattern in HTML body', $url);
            }
            if (preg_match('#([a-zA-Z]:\\\\|/var/www/|/xampp/htdocs/)#', $body) === 1) {
                mynak_audit_add($findings, 'critical', 'path_leak', 'Possible filesystem path in HTML body', $url);
            }
        }
    }

    if ($doSpecial) {
        foreach ($specialEndpoints as $ep) {
            $url = $base . $ep['path'];
            $res = mynak_audit_fetch($url, false);
            if ($res['code'] >= 500) {
                mynak_audit_add($findings, 'critical', 'special_http', "HTTP {$res['code']}", $url);
            } elseif ($res['code'] < 200 || $res['code'] >= 400) {
                mynak_audit_add($findings, 'warning', 'special_http', "HTTP {$res['code']}", $url);
            } else {
                $norm = mynak_audit_apply_normalize($res['body'], $res['headers'], $normalize, false, $findings, $url);
                $b = ltrim($norm['body'], " \t\r\n");
                if (str_ends_with($ep['path'], '.xml')) {
                    if (!empty($normalize['bom'])) {
                        $b = ltrim($b, "\xEF\xBB\xBF");
                    }
                    if (!str_starts_with($b, '<?xml')) {
                        mynak_audit_add($findings, 'warning', 'special_body', 'Expected XML preamble', $url);
                    }
                } elseif ($ep['path'] === '/robots.txt' || $ep['path'] === '/llms.txt') {
                    $low = strtolower(substr($b, 0, 12));
                    if (!str_starts_with($low, 'user-agent:') && $b !== '' && $b[0] !== '#') {
                        mynak_audit_add($findings, 'warning', 'special_body', 'robots/llms: unexpected start', $url);
                    }
                }
                mynak_audit_add($findings, 'info', 'special_ok', 'OK', $url);
            }
        }
    }

    if ($doHttp || $doAll) {
        $fcTests = [
            $base . '/blog.php',
            $base . '/iletisim.php',
        ];
        foreach ($fcTests as $url) {
            $res = mynak_audit_fetch($url, true);
            if ($res['code'] < 200 || $res['code'] >= 500) {
                mynak_audit_add($findings, 'warning', 'fc_php_entry', "HEAD {$res['code']} (beklenen: 2xx, FC üzerinden)", $url);
            } else {
                mynak_audit_add($findings, 'info', 'fc_php_entry', "HEAD {$res['code']}", $url);
            }
        }
    }
}

// --- main ---
/** @var array<string, mixed> $opts */
$opts = mynak_audit_parse_args($argv);
$findings = [];
$normalize = $opts['normalize'];
if (!is_array($normalize)) {
    $normalize = mynak_audit_default_opts()['normalize'];
}

if ($opts['check'] !== 'none') {
    mynak_audit_run(
        $opts['target'],
        $opts['mode'],
        $opts['check'],
        $findings,
        $normalize,
        (bool) $opts['verify_first_byte']
    );
}

$auditEnvPath = is_string($opts['audit_env']) ? $opts['audit_env'] : '/audit_env';
if ($auditEnvPath === '' || $auditEnvPath[0] !== '/') {
    $auditEnvPath = '/audit_env';
}
$needProbe = $opts['compare'] === 'php-cli-vs-http'
    || (bool) $opts['verify_display_errors']
    || (bool) $opts['verify_sapi_drift'];

if ($needProbe) {
    $projectRoot = dirname(__DIR__);
    $cliSnap = mynak_audit_cli_snapshot_after_bootstrap($projectRoot);
    $secret = getenv('MYNAK_AUDIT_SECRET');
    $secret = is_string($secret) && $secret !== '' ? $secret : null;
    $probeUrl = $opts['target'] . $auditEnvPath;
    $httpSnap = mynak_audit_http_env_json($probeUrl, $secret);
    mynak_audit_compare_cli_http(
        $findings,
        $opts['target'],
        $cliSnap,
        $httpSnap,
        $opts['mode'],
        $auditEnvPath,
        $opts['compare'] === 'php-cli-vs-http',
        (bool) $opts['verify_display_errors'],
        (bool) $opts['verify_sapi_drift']
    );
}

$critical = 0;
$warning = 0;
foreach ($findings as $f) {
    $line = sprintf("[%s] %s: %s%s\n", strtoupper($f['level']), $f['check'], $f['message'], $f['url'] !== '' ? " ({$f['url']})" : '');
    if ($f['level'] === 'critical') {
        $critical++;
        fwrite(STDERR, $line);
    } elseif ($f['level'] === 'warning') {
        $warning++;
        fwrite(STDOUT, $line);
    } else {
        fwrite(STDOUT, $line);
    }
}

$exit = 0;
switch ($opts['fail_on']) {
    case 'all':
        if ($critical > 0 || $warning > 0) {
            $exit = 1;
        }
        break;
    case 'warning':
        if ($warning > 0 || $critical > 0) {
            $exit = 1;
        }
        break;
    case 'critical':
    default:
        if ($critical > 0) {
            $exit = 1;
        }
        break;
}

fwrite(STDOUT, sprintf(
    "--- mynak_full_audit mode=%s audit_env=%s normalize=%s verify_first_byte=%s verify_display_errors=%s verify_sapi_drift=%s compare=%s fail-on=%s critical=%d warning=%d exit=%d ---\n",
    $opts['mode'],
    $auditEnvPath,
    implode(',', array_keys(array_filter($normalize))),
    $opts['verify_first_byte'] ? '1' : '0',
    $opts['verify_display_errors'] ? '1' : '0',
    $opts['verify_sapi_drift'] ? '1' : '0',
    $opts['compare'] !== '' ? $opts['compare'] : '-',
    $opts['fail_on'],
    $critical,
    $warning,
    $exit
));

exit($exit);

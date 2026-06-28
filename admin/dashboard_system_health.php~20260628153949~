<?php
/**
 * Dashboard — Sistem Sağlığı modülü.
 *
 * Okur (yazmaz) sağlık sinyalleri:
 *  - OPcache: aktif mi, hit-rate, bellek kullanımı, script sayısı
 *  - PHP versiyonu + memory_limit
 *  - Uygulama page-cache klasörü boyutu + dosya sayısı + en eski giriş
 *  - Disk kullanımı (proje kök volume)
 *  - PHPStan baseline varsa son analiz zamanı
 *
 * Tasarım: Mevcut SEO paneli kart stiline uyumlu; yeni CSS/JS eklemez.
 */

declare(strict_types=1);

if (!function_exists('mynak_dashboard_system_health_collect')) {
    /**
     * @return array{
     *   opcache: array{enabled:bool,status:?array,hit_rate:?float,memory_used_pct:?float,num_cached:?int,misses:?int,reason:?string},
     *   php: array{version:string,sapi:string,memory_limit:string,max_execution_time:string},
     *   cache: array{dir:string,exists:bool,file_count:int,total_bytes:int,oldest_seconds:?int},
     *   disk: array{free_bytes:?int,total_bytes:?int,used_pct:?int},
     *   phpstan: array{exists:bool,last_run_mtime:?int,baseline_count:?int}
     * }
     */
    function mynak_dashboard_system_health_collect(): array
    {
        $out = [
            'opcache' => [
                'enabled' => false,
                'status' => null,
                'hit_rate' => null,
                'memory_used_pct' => null,
                'num_cached' => null,
                'misses' => null,
                'reason' => null,
            ],
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'memory_limit' => (string) ini_get('memory_limit'),
                'max_execution_time' => (string) ini_get('max_execution_time'),
            ],
            'cache' => [
                'dir' => '',
                'exists' => false,
                'file_count' => 0,
                'total_bytes' => 0,
                'oldest_seconds' => null,
            ],
            'disk' => [
                'free_bytes' => null,
                'total_bytes' => null,
                'used_pct' => null,
            ],
            'phpstan' => [
                'exists' => false,
                'last_run_mtime' => null,
                'baseline_count' => null,
            ],
        ];

        // --- OPcache ------------------------------------------------------
        if (function_exists('opcache_get_status')) {
            $opEnabled = (bool) ini_get('opcache.enable');
            $out['opcache']['enabled'] = $opEnabled;
            if ($opEnabled) {
                $status = @opcache_get_status(false);
                if (is_array($status)) {
                    $out['opcache']['status'] = $status;
                    $hits = (int) ($status['opcache_statistics']['hits'] ?? 0);
                    $misses = (int) ($status['opcache_statistics']['misses'] ?? 0);
                    $total = $hits + $misses;
                    if ($total > 0) {
                        $out['opcache']['hit_rate'] = round($hits / $total * 100, 1);
                    }
                    $out['opcache']['misses'] = $misses;
                    $out['opcache']['num_cached'] = (int) ($status['opcache_statistics']['num_cached_scripts'] ?? 0);
                    $memUsed = (int) ($status['memory_usage']['used_memory'] ?? 0);
                    $memFree = (int) ($status['memory_usage']['free_memory'] ?? 0);
                    $memWasted = (int) ($status['memory_usage']['wasted_memory'] ?? 0);
                    $memTotal = $memUsed + $memFree + $memWasted;
                    if ($memTotal > 0) {
                        $out['opcache']['memory_used_pct'] = round(($memUsed + $memWasted) / $memTotal * 100, 1);
                    }
                } else {
                    $out['opcache']['reason'] = 'opcache_get_status() disabled (restrict_api set)';
                }
            } else {
                $out['opcache']['reason'] = 'opcache.enable=0 in php.ini (web SAPI)';
            }
        } else {
            $out['opcache']['reason'] = 'OPcache extension not loaded';
        }

        // --- Uygulama page cache (cache/ klasörü) ------------------------
        $projectRoot = defined('PROJECT_ROOT') ? PROJECT_ROOT : realpath(__DIR__ . '/../..');
        $cacheDir = is_string($projectRoot) && $projectRoot !== ''
            ? rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cache'
            : '';
        $out['cache']['dir'] = $cacheDir;
        if ($cacheDir !== '' && is_dir($cacheDir)) {
            $out['cache']['exists'] = true;
            $count = 0;
            $bytes = 0;
            $oldestMtime = null;
            $it = @new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($it as $f) {
                if ($f instanceof SplFileInfo && $f->isFile()) {
                    $count++;
                    $bytes += (int) $f->getSize();
                    $m = (int) $f->getMTime();
                    if ($oldestMtime === null || $m < $oldestMtime) {
                        $oldestMtime = $m;
                    }
                    if ($count > 2000) { break; }
                }
            }
            $out['cache']['file_count'] = $count;
            $out['cache']['total_bytes'] = $bytes;
            $out['cache']['oldest_seconds'] = $oldestMtime !== null ? max(0, time() - $oldestMtime) : null;
        }

        // --- Disk ---------------------------------------------------------
        if (is_string($projectRoot) && $projectRoot !== '') {
            $free = @disk_free_space($projectRoot);
            $total = @disk_total_space($projectRoot);
            if (is_float($free) && $free > 0) {
                $out['disk']['free_bytes'] = (int) $free;
            }
            if (is_float($total) && $total > 0) {
                $out['disk']['total_bytes'] = (int) $total;
                if (is_int($out['disk']['free_bytes'])) {
                    $out['disk']['used_pct'] = (int) round((($total - $out['disk']['free_bytes']) / $total) * 100);
                }
            }
        }

        // --- PHPStan baseline ---------------------------------------------
        $stanBase = is_string($projectRoot) && $projectRoot !== ''
            ? rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'phpstan-baseline.neon'
            : '';
        if ($stanBase !== '' && is_readable($stanBase)) {
            $out['phpstan']['exists'] = true;
            $out['phpstan']['last_run_mtime'] = (int) filemtime($stanBase);
            // Kaba sayım: "message:" satır adeti
            $lines = @file($stanBase, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                $out['phpstan']['baseline_count'] = count(array_filter($lines, static function ($l) {
                    return strpos((string) $l, 'message:') !== false;
                }));
            }
        }

        return $out;
    }
}

if (!function_exists('mynak_format_bytes')) {
    function mynak_format_bytes(int $bytes): string
    {
        if ($bytes <= 0) { return '0 B'; }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes) / log(1024));
        $i = max(0, min($i, count($units) - 1));
        return sprintf('%.1f %s', $bytes / pow(1024, $i), $units[$i]);
    }
}

if (!function_exists('mynak_format_duration')) {
    function mynak_format_duration(int $seconds): string
    {
        if ($seconds < 60) { return $seconds . ' sn'; }
        if ($seconds < 3600) { return (int) round($seconds / 60) . ' dk'; }
        if ($seconds < 86400) { return (int) round($seconds / 3600) . ' sa'; }
        return (int) round($seconds / 86400) . ' gün';
    }
}

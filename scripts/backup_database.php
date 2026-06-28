<?php
/**
 * C4: Veritabanı yedeği (mysqldump) + eski dosyaları temizleme (varsayılan 30 gün).
 *
 * - Önce .env (DB_*) dener; yoksa XAMPP yerel varsayılanları kullanır (db.php’deki CLI mantığına paralel;
 *   canlı sunucuda yedek alırken proje kökünde .env bulunmalı).
 * - Çıktı: storage/db_backups/{DB_NAME}_YYYY-mm-dd_HHii.sql
 *
 * Kullanım:
 *   php scripts/backup_database.php
 *   php scripts/backup_database.php --days=30
 *   php scripts/backup_database.php --mysqldump="C:\xampp\mysql\bin\mysqldump.exe"
 *   php scripts/backup_database.php --dry-run   (sadece ne yapacağını yazar, dosya üretmez; mysqldump yoksa uyarır, çıkmaz)
 *   php scripts/backup_database.php --help
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$root = realpath(__DIR__ . '/..') ?: dirname(__DIR__);

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    $bin = 'php scripts/backup_database.php';
    echo <<<HELP
C4: Veritabani yedegi (mysqldump) + eski .sql temizligi.

  {$bin}
  {$bin} --days=30
  {$bin} --dry-run
  {$bin} --mysqldump="C:\\xampp\\mysql\\bin\\mysqldump.exe"

Ortam: .env (DB_*, istege bagli MYSQLDUMP_PATH). Yerel: .env yoksa XAMPP root/root/mynakliyat.
Cikti: storage/db_backups/{DB_NAME}_YYYY-mm-dd_HHMMSS.sql

HELP;
    exit(0);
}

require_once $root . '/config/env_loader.php';
mynak_load_dotenv($root . DIRECTORY_SEPARATOR . '.env');

$dry = false;
$days = 30;
$mysqldumpPath = mynak_env_str('MYSQLDUMP_PATH');
if ($mysqldumpPath === '' && is_readable($root . '/.env') === false) {
    $mysqldumpPath = '';
}

foreach ($argv as $i => $a) {
    if ($a === '--dry-run') {
        $dry = true;
    }
    if (preg_match('/^--days=(\d+)$/', $a, $m)) {
        $days = max(0, (int) $m[1]);
    }
    if (preg_match('/^--mysqldump=(.+)$/', $a, $m)) {
        $mysqldumpPath = trim($m[1], " \t\"'");
    }
}

$host = trim(mynak_env_str('DB_HOST'));
$user = trim(mynak_env_str('DB_USER'));
$pass = mynak_env_str('DB_PASS');
$name = trim(mynak_env_str('DB_NAME'));
$port = (int) (trim(mynak_env_str('DB_PORT')) ?: '3306');
if ($port < 1) {
    $port = 3306;
}

if ($name === '' || $user === '') {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $name = 'mynakliyat';
    $port = 3306;
    fwrite(STDERR, "Not: .env yok veya DB_USER/DB_NAME bos — yerel XAMPP varsayilanlari kullaniliyor.\n");
}

$backupDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'db_backups';
if (!is_dir($backupDir)) {
    if (!$dry) {
        if (!@mkdir($backupDir, 0750, true) && !is_dir($backupDir)) {
            fwrite(STDERR, "Klasor olusturulamadi: $backupDir\n");
            exit(1);
        }
    }
}

if ($dry) {
    echo "[DRY-RUN] Yedek klasoru: $backupDir\n";
} else {
    echo "Yedek klasoru: $backupDir\n";
}

$mysqldump = mynak_resolve_mysqldump_path($mysqldumpPath !== '' ? $mysqldumpPath : null);
$mysqldumpOk = $mysqldump !== '' && is_file($mysqldump);
if (!$mysqldumpOk) {
    if ($dry) {
        fwrite(STDERR, "UYARI: mysqldump bulunamadi; asagidaki cikti/yol bilgilendirme amaclidir. Gercek yedek icin --mysqldump=... veya MYSQLDUMP_PATH.\n");
        $mysqldump = '(mysqldump yok)';
    } else {
        fwrite(STDERR, "mysqldump bulunamadi. --mysqldump=... veya ortam degiskeni MYSQLDUMP_PATH verin.\n");
        fwrite(STDERR, "Ornek (XAMPP): C:\\xampp\\mysql\\bin\\mysqldump.exe\n");
        exit(1);
    }
}
echo "mysqldump: $mysqldump\n";

$stamp = date('Y-m-d_His');
$outFile = $backupDir . DIRECTORY_SEPARATOR . $name . '_' . $stamp . '.sql';
echo "Cikti dosyasi: $outFile\n";

if ($dry) {
    echo "[DRY-RUN] Yedek dosyasi yazilmadi.\n";
} else {
    $lockPath = $backupDir . DIRECTORY_SEPARATOR . '.backup.lock';
    $lockFh = @fopen($lockPath, 'c+');
    if ($lockFh === false) {
        fwrite(STDERR, "Kilit dosyasi acilamadi: $lockPath\n");
        exit(1);
    }
    if (!@flock($lockFh, LOCK_EX | LOCK_NB)) {
        fclose($lockFh);
        fwrite(STDERR, "Baska bir yedek islemi calisiyor. Kisa sure sonra tekrar deneyin.\n");
        exit(1);
    }
    $GLOBALS['mynak_backup_lock_fh'] = $lockFh;
    register_shutdown_function(static function (): void {
        $h = $GLOBALS['mynak_backup_lock_fh'] ?? null;
        if (is_resource($h)) {
            flock($h, LOCK_UN);
            fclose($h);
        }
        unset($GLOBALS['mynak_backup_lock_fh']);
    });

    $cnf = @tempnam(sys_get_temp_dir(), 'mndump_');
    if ($cnf === false) {
        fwrite(STDERR, "Gecici dosya olusturulamadi.\n");
        exit(1);
    }
    $cnfContent = "[client]\n" .
        'user=' . $user . "\n" .
        'password=' . str_replace(["\n", "\r"], '', $pass) . "\n" .
        'host=' . $host . "\n" .
        'port=' . (string) $port . "\n";
    if (file_put_contents($cnf, $cnfContent) === false) {
        @unlink($cnf);
        fwrite(STDERR, "Gecici my.cnf yazilamadi.\n");
        exit(1);
    }

    // --defaults-file MySQL belgesine gore komutta ilk olmali; proc_open dizi siralamasini korur.
    // Windows: yoldaki \m, \n vb. tekil ters eğik argümanı bölebiliyor — mysqldump / kullanıcıyı karıştırıyor.
    $outNorm = str_replace('\\', '/', $outFile);
    $cnfForArg = str_replace('\\', '/', $cnf);
    $args = [
        $mysqldump,
        '--defaults-file=' . $cnfForArg,
        '--single-transaction',
        '--routines',
        '--events',
        '--triggers',
        '--default-character-set=utf8mb4',
        '--result-file=' . $outNorm,
        $name,
    ];

    $code = 0;
    $err = '';
    if (function_exists('proc_open')) {
        $d = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $opts = PHP_OS_FAMILY === 'Windows' ? ['bypass_shell' => true] : [];
        $p = @proc_open($args, $d, $pipes, $root, null, $opts);
        if (is_resource($p)) {
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($p);
        } else {
            $code = 127;
        }
    }
    if ($code !== 0) {
        @unlink($cnf);
        fwrite(STDERR, "mysqldump hata kodu: $code\n");
        if ($err !== '') {
            fwrite(STDERR, $err . "\n");
        }
        if (is_file($outFile)) {
            @unlink($outFile);
        }
        exit(1);
    }
    @unlink($cnf);
    $size = is_file($outFile) ? (int) filesize($outFile) : 0;
    if ($size < 1) {
        fwrite(STDERR, "Yedek dosyasi bos veya yok.\n");
        if (is_file($outFile)) {
            @unlink($outFile);
        }
        exit(1);
    }
    echo "Tamam. Boyut: " . number_format($size) . " bayt\n";
}

$cutoff = time() - ($days * 86400);
$removed = 0;
if (is_dir($backupDir) && $days > 0) {
    $pattern = $backupDir . DIRECTORY_SEPARATOR . '*.sql';
    foreach (glob($pattern) ?: [] as $f) {
        if (!is_file($f)) {
            continue;
        }
        $mt = (int) filemtime($f);
        if ($mt >= $cutoff) {
            continue;
        }
        if ($dry) {
            echo "[DRY-RUN] Silinecek (>" . (string) $days . " gun): $f\n";
        } else {
            if (@unlink($f)) {
                $removed++;
                echo "Eski yedek silindi: " . basename($f) . "\n";
            }
        }
    }
}
if ($removed === 0 && !$dry) {
    echo "Rotasyon: silinecek eski yedek yok (>" . (string) $days . " gun).\n";
}

echo "\nUptime: scripts/C4_uptime_rehber_kisa.txt\n";
exit(0);

/**
 * @param null|string $override Path from --mysqldump or MYSQLDUMP_PATH
 */
function mynak_resolve_mysqldump_path(?string $override): string
{
    if ($override !== null && $override !== '') {
        if (is_file($override)) {
            return $override;
        }
    }
    $candidates = [];
    if (defined('PHP_BINARY')) {
        $php = (string) PHP_BINARY;
        $xampp = dirname(dirname($php));
        $candidates[] = $xampp . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
    }
    $candidates[] = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    if (DIRECTORY_SEPARATOR === '/') {
        $candidates[] = '/usr/bin/mysqldump';
        $candidates[] = '/usr/local/bin/mysqldump';
    }
    foreach ($candidates as $c) {
        if ($c !== '' && is_file($c)) {
            return $c;
        }
    }

    if (DIRECTORY_SEPARATOR === '\\') {
        $out = [];
        $code = 0;
        @exec('where mysqldump 2>NUL', $out, $code);
        if ($code === 0 && isset($out[0]) && is_file($out[0])) {
            return $out[0];
        }
    } else {
        $out = [];
        $code = 0;
        @exec('command -v mysqldump 2>/dev/null', $out, $code);
        if ($code === 0 && isset($out[0]) && is_file($out[0])) {
            return $out[0];
        }
    }

    return '';
}

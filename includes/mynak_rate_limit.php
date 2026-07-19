<?php
declare(strict_types=1);

/**
 * Hafif, dosya tabanlı rate limiter (brute-force / spam azaltma).
 *
 * Depolama: cache/ratelimit/<sha1(anahtar)>.json — pencere içindeki
 * zaman damgası listesi. Ek DB tablosu gerektirmez; paylaşımlı hostingde
 * (cPanel) çalışır. Depolama yazılamazsa "fail-open" davranır: meşru
 * kullanıcıyı kilitlemektense sınırlamayı atlar.
 *
 * Anahtar örneği: 'admin_login|<ip>' veya 'process_form|<ip>'.
 */

if (function_exists('mynak_rate_limit_status')) {
    return;
}

/**
 * Rate limit veri klasörü (yoksa oluşturur).
 */
function mynak_rate_limit_dir(): string
{
    $dir = dirname(__DIR__) . '/cache/ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

/**
 * İstemci IP'si. Proxy başlıklarına körü körüne güvenilmez;
 * REMOTE_ADDR en güvenilir varsayılandır.
 */
function mynak_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (is_string($ip) && $ip !== '') {
        return $ip;
    }
    return '0.0.0.0';
}

/**
 * @internal Anahtar için depolama dosyası yolu.
 */
function mynak_rate_limit_file(string $key): string
{
    return mynak_rate_limit_dir() . '/' . sha1($key) . '.json';
}

/**
 * @internal Pencere içindeki zaman damgalarını yükler (eskiyenleri eler).
 *
 * @return array<int, int>
 */
function mynak_rate_limit_load(string $key, int $window): array
{
    $file = mynak_rate_limit_file($key);
    if (!is_file($file)) {
        return [];
    }
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    $now = time();
    $out = [];
    foreach ($decoded as $t) {
        $t = (int) $t;
        if ($t > 0 && ($now - $t) < $window) {
            $out[] = $t;
        }
    }
    return $out;
}

/**
 * @internal Zaman damgası listesini diske yazar.
 *
 * @param array<int, int> $timestamps
 */
function mynak_rate_limit_save(string $key, array $timestamps): void
{
    $file = mynak_rate_limit_file($key);
    @file_put_contents($file, json_encode(array_values($timestamps)), LOCK_EX);
}

/**
 * Kayıt EKLEMEDEN mevcut durumu döndürür (yalnızca okur/kontrol eder).
 *
 * @return array{ok: bool, count: int, remaining: int, retry_after: int}
 */
function mynak_rate_limit_status(string $key, int $max, int $window): array
{
    $data = mynak_rate_limit_load($key, $window);
    $count = count($data);
    $ok = $count < $max;
    $retryAfter = 0;
    if (!$ok && $data !== []) {
        $oldest = min($data);
        $retryAfter = max(0, $window - (time() - $oldest));
    }
    return [
        'ok' => $ok,
        'count' => $count,
        'remaining' => max(0, $max - $count),
        'retry_after' => $retryAfter,
    ];
}

/**
 * Bir deneme kaydeder (zaman damgası ekler).
 */
function mynak_rate_limit_register(string $key, int $window): void
{
    $data = mynak_rate_limit_load($key, $window);
    $data[] = time();
    mynak_rate_limit_save($key, $data);
}

/**
 * Anahtarı sıfırlar (ör. başarılı girişten sonra).
 */
function mynak_rate_limit_clear(string $key): void
{
    $file = mynak_rate_limit_file($key);
    if (is_file($file)) {
        @unlink($file);
    }
}

/**
 * Geriye dönük uyumlu kontrol: durumu okur ve izinliyse denemeyi kaydeder.
 * (ajax/process_form.php bu imzayı kullanır.)
 *
 * @return array{ok: bool, count: int, remaining: int, retry_after: int}
 */
function mynak_rate_limit_check(string $action, int $max, int $window): array
{
    $key = $action . '|' . mynak_client_ip();
    $status = mynak_rate_limit_status($key, $max, $window);
    if ($status['ok']) {
        mynak_rate_limit_register($key, $window);
    }
    return $status;
}

<?php
declare(strict_types=1);

/**
 * Google İşletme Profili (Places Details) → cache/gbp_data.json
 * Admin: settings.php “Google İşletme Saatlerini Şimdi Güncelle”
 */

function mynak_gbp_cache_file_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'gbp_data.json';
}

/**
 * @return array<string, mixed>
 */
function mynak_gbp_read_cache(): array
{
    $path = mynak_gbp_cache_file_path();
    if (!is_readable($path)) {
        return [];
    }
    $decoded = json_decode((string) file_get_contents($path), true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * @return list<array<string, mixed>>
 */
function mynak_gbp_cached_opening_hours_specs(): array
{
    $cache = mynak_gbp_read_cache();
    $specs = $cache['opening_hours_spec'] ?? [];
    if (!is_array($specs) || $specs === []) {
        return [];
    }

    return $specs;
}

function mynak_gbp_setting_get(mysqli $conn, string $name): string
{
    $stmt = $conn->prepare('SELECT value FROM settings WHERE name = ? LIMIT 1');
    if (!$stmt) {
        return '';
    }
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ? trim((string) $row['value']) : '';
}

function mynak_gbp_setting_upsert(mysqli $conn, string $name, string $value, string $description = ''): void
{
    $stmt = $conn->prepare(
        'INSERT INTO settings (name, value, description) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value)'
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('sss', $name, $value, $description);
    $stmt->execute();
    $stmt->close();
}

function mynak_gbp_format_google_time(string $raw): string
{
    $digits = preg_replace('/\D/', '', $raw) ?? '';
    $digits = str_pad($digits, 4, '0', STR_PAD_LEFT);

    return substr($digits, 0, 2) . ':' . substr($digits, 2, 2);
}

/**
 * Google Places opening_hours.periods → schema.org OpeningHoursSpecification
 *
 * @param list<array<string, mixed>> $periods
 * @return list<array<string, mixed>>
 */
function mynak_gbp_periods_to_opening_specs(array $periods): array
{
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    /** @var array<string, list<string>> $bySlot */
    $bySlot = [];

    foreach ($periods as $period) {
        if (!is_array($period) || !isset($period['open']['day'], $period['open']['time'])) {
            continue;
        }
        $openDay = (int) $period['open']['day'];
        $opens = mynak_gbp_format_google_time((string) $period['open']['time']);
        $closes = isset($period['close']['time'])
            ? mynak_gbp_format_google_time((string) $period['close']['time'])
            : '23:59';
        $slotKey = $opens . '|' . $closes;
        if (!isset($bySlot[$slotKey])) {
            $bySlot[$slotKey] = [];
        }
        $dayLabel = $dayNames[$openDay] ?? 'Monday';
        if (!in_array($dayLabel, $bySlot[$slotKey], true)) {
            $bySlot[$slotKey][] = $dayLabel;
        }
    }

    $specs = [];
    foreach ($bySlot as $slotKey => $days) {
        [$opens, $closes] = explode('|', $slotKey, 2);
        $node = [
            '@type' => 'OpeningHoursSpecification',
            'opens' => $opens,
            'closes' => $closes,
        ];
        $node['dayOfWeek'] = count($days) === 1 ? $days[0] : array_values($days);
        $specs[] = $node;
    }

    return $specs;
}

/**
 * @return array{ok: bool, message: string}
 */
function mynak_sync_gbp_data(mysqli $conn, bool $forceRefresh = false): array
{
    $apiKey = mynak_gbp_setting_get($conn, 'google_places_api_key');
    $placeId = mynak_gbp_setting_get($conn, 'google_place_id');

    if ($apiKey === '' || $placeId === '') {
        return [
            'ok' => false,
            'message' => 'Google Places API Key ve Place ID gerekli. Admin → Google Yorumlar sayfasından girin.',
        ];
    }

    $cachePath = mynak_gbp_cache_file_path();
    $cacheDir = dirname($cachePath);
    if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
        return ['ok' => false, 'message' => 'cache/ klasörü oluşturulamadı.'];
    }

    if (!$forceRefresh) {
        $existing = mynak_gbp_read_cache();
        $syncedAt = (int) ($existing['synced_at'] ?? 0);
        if ($syncedAt > 0 && (time() - $syncedAt) < 86400) {
            return ['ok' => true, 'message' => 'Önbellek güncel (son 24 saat içinde senkronize edildi).'];
        }
    }

    $fields = 'place_id,name,rating,user_ratings_total,url,opening_hours,current_opening_hours';
    $url = 'https://maps.googleapis.com/maps/api/place/details/json?'
        . 'place_id=' . urlencode($placeId)
        . '&fields=' . urlencode($fields)
        . '&language=tr'
        . '&key=' . urlencode($apiKey);

    $responseBody = false;
    $httpStatus = 0;
    $curlError = '';

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $responseBody = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = (string) curl_error($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 20]]);
        $responseBody = @file_get_contents($url, false, $ctx);
        if (is_array($http_response_header ?? null)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $hdr, $m)) {
                    $httpStatus = (int) $m[1];
                    break;
                }
            }
        }
    }

    if ($responseBody === false || $responseBody === '') {
        return [
            'ok' => false,
            'message' => 'Google API isteği başarısız.'
                . ($curlError !== '' ? ' cURL: ' . $curlError : ''),
        ];
    }

    $data = json_decode((string) $responseBody, true);
    if (!is_array($data)) {
        return ['ok' => false, 'message' => 'Google API yanıtı okunamadı (JSON).'];
    }

    $status = (string) ($data['status'] ?? '');
    if ($status !== 'OK' || !isset($data['result']) || !is_array($data['result'])) {
        $err = (string) ($data['error_message'] ?? $status ?: 'Bilinmeyen hata');

        return ['ok' => false, 'message' => 'Google API: ' . $err];
    }

    $result = $data['result'];
    $opening = $result['opening_hours'] ?? $result['current_opening_hours'] ?? [];
    if (!is_array($opening)) {
        $opening = [];
    }
    $periods = isset($opening['periods']) && is_array($opening['periods']) ? $opening['periods'] : [];
    $weekdayText = isset($opening['weekday_text']) && is_array($opening['weekday_text'])
        ? array_values(array_map('strval', $opening['weekday_text']))
        : [];

    $rating = isset($result['rating']) ? (float) $result['rating'] : 0.0;
    $totalReviews = isset($result['user_ratings_total']) ? (int) $result['user_ratings_total'] : 0;
    $mapsUrl = trim((string) ($result['url'] ?? ''));
    $placeName = trim((string) ($result['name'] ?? ''));

    $openingSpecs = mynak_gbp_periods_to_opening_specs($periods);

    $payload = [
        'opening_hours_spec' => $openingSpecs,
        'weekday_text' => $weekdayText,
        'place_id' => $placeId,
        'place_name' => $placeName,
        'source' => 'places_details',
        'http_status' => $httpStatus,
        'error' => null,
        'synced_at' => time(),
        'rating' => $rating,
        'user_ratings_total' => $totalReviews,
    ];

    $written = @file_put_contents(
        $cachePath,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    if ($written === false) {
        return ['ok' => false, 'message' => 'cache/gbp_data.json yazılamadı (izin kontrolü).'];
    }

    if ($rating > 0) {
        mynak_gbp_setting_upsert($conn, 'google_place_rating', (string) $rating, 'GBP yıldız ortalaması');
    }
    if ($totalReviews > 0) {
        mynak_gbp_setting_upsert($conn, 'google_total_reviews', (string) $totalReviews, 'GBP toplam yorum sayısı');
    }
    if ($mapsUrl !== '') {
        mynak_gbp_setting_upsert($conn, 'google_maps_url', $mapsUrl, 'Google Maps işletme URL');
    }
    if ($placeName !== '') {
        mynak_gbp_setting_upsert($conn, 'google_place_name', $placeName, 'Google işletme adı');
    }
    mynak_gbp_setting_upsert($conn, 'google_gbp_last_sync', date('Y-m-d H:i:s'), 'GBP saat/puan son senkron');

    $hoursNote = $openingSpecs !== [] ? count($openingSpecs) . ' çalışma saati bloğu' : 'saat bilgisi yok (API boş döndü)';

    return [
        'ok' => true,
        'message' => sprintf(
            'Senkron tamam: %s — %.1f/5 (%d yorum), %s.',
            $placeName !== '' ? $placeName : 'İşletme',
            $rating,
            $totalReviews,
            $hoursNote
        ),
    ];
}

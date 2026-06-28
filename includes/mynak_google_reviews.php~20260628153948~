<?php
declare(strict_types=1);

/**
 * Google Business Profile yorumları — DB (google_reviews) + vitrin render.
 */

/**
 * @return array{
 *   reviews_rows: list<array<string, mixed>>,
 *   g_maps_url: string,
 *   g_total: string,
 *   g_rating: string,
 *   avatar_colors: list<string>
 * }|null
 */
function mynak_google_reviews_fetch(mysqli $conn, int $limit = 25): ?array
{
    $limit = max(1, min(50, $limit));
    $table = $conn->query("SHOW TABLES LIKE 'google_reviews'");
    if (!$table || $table->num_rows === 0) {
        return null;
    }

    $g_reviews = $conn->query(
        'SELECT * FROM google_reviews WHERE status = 1 ORDER BY time DESC LIMIT ' . (int) $limit
    );
    if (!$g_reviews || $g_reviews->num_rows === 0) {
        return null;
    }

    $g_maps_url_q = $conn->query("SELECT value FROM settings WHERE name = 'google_maps_url' LIMIT 1");
    $g_maps_url = ($g_maps_url_q && ($row = $g_maps_url_q->fetch_assoc())) ? (string) $row['value'] : '';
    if ($g_maps_url === '') {
        $g_place_id_q = $conn->query("SELECT value FROM settings WHERE name = 'google_place_id' LIMIT 1");
        $g_place_id_val = ($g_place_id_q && ($row = $g_place_id_q->fetch_assoc())) ? (string) $row['value'] : '';
        $g_maps_url = $g_place_id_val !== ''
            ? 'https://search.google.com/local/reviews?placeid=' . rawurlencode($g_place_id_val)
            : (defined('MYNAK_CONTACT_GOOGLE_MAPS_URL')
                ? MYNAK_CONTACT_GOOGLE_MAPS_URL
                : 'https://maps.app.goo.gl/KhjpeauhbhoXaupZ8');
    }

    $g_total_q = $conn->query("SELECT value FROM settings WHERE name = 'google_total_reviews' LIMIT 1");
    $g_total = ($g_total_q && ($row = $g_total_q->fetch_assoc())) ? (string) $row['value'] : '100';
    $g_rating_q = $conn->query("SELECT value FROM settings WHERE name = 'google_place_rating' LIMIT 1");
    $g_rating = ($g_rating_q && ($row = $g_rating_q->fetch_assoc())) ? (string) $row['value'] : '5.0';

    $reviews_rows = [];
    while ($row = $g_reviews->fetch_assoc()) {
        $reviews_rows[] = $row;
    }

    return [
        'reviews_rows' => $reviews_rows,
        'g_maps_url' => $g_maps_url,
        'g_total' => $g_total,
        'g_rating' => $g_rating,
        'avatar_colors' => ['#E53935', '#8E24AA', '#3949AB', '#00897B', '#F4511E', '#6D4C41', '#546E7A', '#D81B60'],
    ];
}

function mynak_google_reviews_author_initials(string $authorName): string
{
    $nameParts = preg_split('/\s+/u', trim($authorName)) ?: [];
    $initials = '';
    if (isset($nameParts[0]) && $nameParts[0] !== '') {
        $initials .= mb_strtoupper(mb_substr($nameParts[0], 0, 1, 'UTF-8'), 'UTF-8');
    }
    if (isset($nameParts[1]) && $nameParts[1] !== '') {
        $initials .= mb_strtoupper(mb_substr($nameParts[1], 0, 1, 'UTF-8'), 'UTF-8');
    }

    return $initials !== '' ? $initials : '?';
}

/**
 * Statik grid (yorumlar sayfası) veya marquee (ana sayfa) layout.
 */
function mynak_google_reviews_render(array $data, string $layout = 'grid'): string
{
    if (($data['reviews_rows'] ?? []) === []) {
        return '';
    }

    ob_start();
    $partial = __DIR__ . '/partials/mynak_google_reviews_' . ($layout === 'marquee' ? 'marquee' : 'grid') . '.php';
    if (is_readable($partial)) {
        include $partial;
    }

    return (string) ob_get_clean();
}

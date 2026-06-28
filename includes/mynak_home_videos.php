<?php
declare(strict_types=1);

/**
 * Ana sayfa YouTube vitrin + VideoObject (JSON-LD) için ortak parse.
 * Ayar: settings.mynak_home_youtube_ids (JSON, max 3 öğe).
 */
function mynak_youtube_id_normalize_from_user_input(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('/^([a-zA-Z0-9_-]{11})$/', $raw, $m)) {
        return $m[1];
    }
    $patterns = [
        '~(?:youtube\.com/(?:embed/|watch\?v=)|youtu\.be/)([a-zA-Z0-9_-]{11})~',
        '~youtube\.com/shorts/([a-zA-Z0-9_-]{11})~i',
        '~youtube-nocookie\.com/embed/([a-zA-Z0-9_-]{11})~i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $raw, $m)) {
            return $m[1];
        }
    }

    return '';
}

/**
 * @return list<array{youtube_id: string, name: string, description: string, upload_date: string}>
 */
function mynak_home_videos_padded_list(array $site_or_slice): array
{
    $raw = trim((string) ($site_or_slice['mynak_home_youtube_ids'] ?? ''));
    $items = [];
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $row) {
                if (is_string($row)) {
                    $id = mynak_youtube_id_normalize_from_user_input($row);
                    if ($id !== '') {
                        $items[] = [
                            'youtube_id' => $id,
                            'name' => '',
                            'description' => '',
                            'upload_date' => '',
                        ];
                    }
                } elseif (is_array($row)) {
                    $id = mynak_youtube_id_normalize_from_user_input(
                        (string) ($row['id'] ?? $row['youtube_id'] ?? '')
                    );
                    if ($id !== '') {
                        $items[] = [
                            'youtube_id' => $id,
                            'name' => trim((string) ($row['name'] ?? $row['title'] ?? '')),
                            'description' => trim((string) ($row['description'] ?? '')),
                            'upload_date' => trim((string) ($row['datePublished'] ?? $row['uploadDate'] ?? '')),
                        ];
                    }
                }
            }
        }
    }
    while (count($items) < 3) {
        $items[] = [
            'youtube_id' => '',
            'name' => '',
            'description' => '',
            'upload_date' => '',
        ];
    }

    return array_slice($items, 0, 3);
}

/**
 * Ana sayfa vitrin: saf HTML5 YouTube embed (JS player / iframe_api yok).
 */
function mynak_home_youtube_embed_iframe(string $videoId, string $title = 'MY Nakliyat Video Rehberi'): string
{
    $id = mynak_youtube_id_normalize_from_user_input($videoId);
    if ($id === '' || !preg_match('/^[a-zA-Z0-9_-]{11}$/', $id)) {
        return '';
    }

    $src = 'https://www.youtube.com/embed/' . $id;
    $titleOut = trim($title) !== '' ? trim($title) : 'MY Nakliyat Video Rehberi';

    return '<iframe'
        . ' src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"'
        . ' title="' . htmlspecialchars($titleOut, ENT_QUOTES, 'UTF-8') . '"'
        . ' frameborder="0"'
        . ' loading="lazy"'
        . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
        . ' referrerpolicy="strict-origin-when-cross-origin"'
        . ' allowfullscreen></iframe>';
}

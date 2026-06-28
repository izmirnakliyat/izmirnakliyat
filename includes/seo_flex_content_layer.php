<?php
declare(strict_types=1);

/**
 * MySQL datetime → ISO 8601 (BlogPosting datePublished / dateModified).
 */
function flex_seo_datetime_to_iso8601(mixed $value): string
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return $raw;
    }

    return date('c', $ts);
}

/**
 * Şema / head pipeline için esnek içerik alanları (DB satırından türetilir).
 *
 * @param array{
 *   page?: ?array<string, mixed>,
 *   blog?: ?array<string, mixed>,
 *   site_settings?: array<string, string>,
 *   page_title?: string,
 *   canonical_page_type?: string
 * } $ctx
 * @return array<string, mixed>
 */
function flex_content_resolver(array $ctx): array
{
    $page = isset($ctx['page']) && is_array($ctx['page']) ? $ctx['page'] : null;
    $blog = isset($ctx['blog']) && is_array($ctx['blog']) ? $ctx['blog'] : null;
    $site_settings = isset($ctx['site_settings']) && is_array($ctx['site_settings']) ? $ctx['site_settings'] : [];
    $page_title = trim((string) ($ctx['page_title'] ?? ''));
    $ptype = (string) ($ctx['canonical_page_type'] ?? 'global');
    $siteTitle = trim((string) ($site_settings['site_title'] ?? ''));

    $out = [];

    switch ($ptype) {
        case 'service':
            if ($page !== null) {
                $name = (string) ($page['title'] ?? $page['ana_baslik'] ?? '');
                $out['service_name'] = $name;
                $out['service_slug'] = (string) ($page['slug'] ?? '');
            } elseif ($blog !== null) {
                $slug = (string) ($blog['slug'] ?? '');
                $faz2File = dirname(__DIR__) . '/includes/mynak_faz2_ilce_seo.php';
                if ($slug !== '' && is_file($faz2File)) {
                    require_once $faz2File;
                    if (function_exists('mynak_faz2_snippets_for_slug')) {
                        $sn = mynak_faz2_snippets_for_slug($slug);
                        if ($sn !== null) {
                            $out['service_name'] = $sn['h1'];
                            $out['service_slug'] = $slug;
                            break;
                        }
                    }
                }
                $out['service_name'] = (string) ($blog['baslik'] ?? '');
                $out['service_slug'] = $slug;
            } else {
                $out['service_name'] = '';
                $out['service_slug'] = '';
            }
            break;

        case 'blog':
        case 'blog_index':
            $out['blog_index_name'] = $page_title !== '' ? $page_title : 'Blog';
            break;

        case 'blog_post':
            $brand = function_exists('mynak_schema_brand') ? mynak_schema_brand() : 'MY Nakliyat';
            $out['organization_name'] = $siteTitle !== '' ? $siteTitle : $brand;
            if ($blog !== null) {
                $slug = trim((string) ($blog['slug'] ?? ''), '/');
                $postUrl = $slug !== '' && function_exists('mynak_abs_url_from_public_path')
                    ? mynak_abs_url_from_public_path(mynak_public_path($slug))
                    : ((defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '') . ($slug !== '' ? '/' . rawurlencode($slug) : '/'));
                $rawHtml = (string) ($blog['icerik'] ?? '');
                $plain = strip_tags($rawHtml);
                $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $plain = trim((string) preg_replace('/\s+/u', ' ', $plain));
                $headline = (string) (!empty($blog['seo_title']) ? $blog['seo_title'] : ($blog['baslik'] ?? ''));
                $wc = $plain !== '' ? count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY)) : 0;
                $out['blog_post'] = [
                    'headline' => $headline,
                    'description' => (string) ($blog['meta_description'] ?? ''),
                    'url' => $postUrl,
                    'date_published' => flex_seo_datetime_to_iso8601($blog['created_at'] ?? ''),
                    'date_modified' => flex_seo_datetime_to_iso8601($blog['updated_at'] ?? $blog['created_at'] ?? ''),
                    'article_body_plain' => $plain,
                    'word_count' => $wc,
                    'image_url' => '',
                    'image_width' => 1200,
                    'image_height' => 675,
                ];
                if (!empty($blog['kapak_foto']) && function_exists('blog_kapak_full_url')) {
                    $out['blog_post']['image_url'] = blog_kapak_full_url((string) $blog['kapak_foto']);
                }
            }
            break;

        case 'contact':
            $out['contact_page_name'] = $page_title !== '' ? $page_title : 'İletişim';
            break;

        case 'team':
            $out['page_title_display'] = $page_title !== '' ? $page_title : 'Ekibimiz';
            break;

        case 'about':
            $out['page_title_display'] = $page_title !== '' ? $page_title : ($siteTitle !== '' ? $siteTitle : 'Hakkımızda');
            $desc = '';
            if ($page !== null && !empty($page['content'])) {
                $t = strip_tags((string) $page['content']);
                $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $t = trim((string) preg_replace('/\s+/u', ' ', $t));
                $desc = mb_substr($t, 0, 500);
            }
            if ($desc === '' && $page !== null && !empty($page['meta_description'])) {
                $desc = trim((string) $page['meta_description']);
            }
            if ($desc === '') {
                if (!empty($site_settings['site_description'])) {
                    $desc = trim((string) $site_settings['site_description']);
                } elseif (!empty($site_settings['short_description'])) {
                    $desc = trim((string) $site_settings['short_description']);
                }
            }
            if ($desc !== '') {
                $desc = mb_substr($desc, 0, 500);
            }
            if ($desc === '' && function_exists('mynak_schema_brand')) {
                $b = mynak_schema_brand();
                $desc = $b . ' — İzmir merkezli evden eve nakliyat, ofis taşıma ve eşya depolama; ISO 9001 belgeli, güvenilir marka ödüllü hizmet.';
                $desc = mb_substr($desc, 0, 500);
            }
            $out['about_description'] = $desc;
            break;

        default:
            break;
    }

    return $out;
}

/**
 * Blog ana liste JSON-LD (ItemList) için satır başlığı + kısa açıklama.
 *
 * @param array<string, mixed> $row blog_posts satırı (slug, baslik, seo_title, icerik, meta_description)
 * @return array{title: string, description: string}
 */
function flex_blog_index_post_text(array $row): array
{
    $title = '';
    if (!empty($row['seo_title'])) {
        $title = trim((string) $row['seo_title']);
    }
    if ($title === '' && !empty($row['baslik'])) {
        $title = trim((string) $row['baslik']);
    }

    $desc = '';
    if (!empty($row['meta_description'])) {
        $desc = trim((string) $row['meta_description']);
    }

    if ($desc === '' && !empty($row['icerik'])) {
        $plain = strip_tags((string) $row['icerik']);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = trim((string) preg_replace('/\s+/u', ' ', $plain));
        if ($plain !== '') {
            if (function_exists('mb_substr')) {
                $desc = mb_strlen($plain, 'UTF-8') > 200
                    ? mb_substr($plain, 0, 200, 'UTF-8') . '…'
                    : $plain;
            } else {
                $desc = strlen($plain) > 200 ? substr($plain, 0, 200) . '…' : $plain;
            }
        }
    }

    return ['title' => $title, 'description' => $desc];
}

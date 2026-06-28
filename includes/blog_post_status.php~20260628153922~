<?php
/**
 * blog_posts.durum — v2 anlamlar (2026):
 *   0 = Taslak
 *   1 = Editör kuyruğu (insan onayı öncesi)
 *   2 = Revize (geri gönderildi / düzeltme)
 *   3 = Yayında (herkese açık)
 *
 * Eski semboller (taşıma öncesi): 0 taslak, 1 yayında, 2 kuyruk, 3 reddedildi
 * → scripts/blog_post_status_semantic_migrate.php
 */

declare(strict_types=1);

const MYNAK_BLOG_STATUS_DRAFT = 0;
const MYNAK_BLOG_STATUS_EDITOR_QUEUE = 1;
const MYNAK_BLOG_STATUS_REVISION = 2;
const MYNAK_BLOG_STATUS_PUBLISHED = 3;

function mynak_blog_is_published(int $durum): bool
{
    return $durum === MYNAK_BLOG_STATUS_PUBLISHED;
}

/** @return non-empty-string */
function mynak_blog_status_label_tr(int $durum): string
{
    switch ($durum) {
        case MYNAK_BLOG_STATUS_DRAFT:
            return 'Taslak';
        case MYNAK_BLOG_STATUS_EDITOR_QUEUE:
            return 'Editör kuyruğu';
        case MYNAK_BLOG_STATUS_REVISION:
            return 'Revize';
        case MYNAK_BLOG_STATUS_PUBLISHED:
            return 'Yayında';
        default:
            return 'Bilinmeyen durum';
    }
}

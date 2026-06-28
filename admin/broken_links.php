<?php
$page_title = 'Kırık Link Tarayıcı';
require_once 'includes/header.php';

$site_url = SITE_URL;
$site_domains = [
    'mynakliyat.com.tr',
    'www.mynakliyat.com.tr',
    'localhost/mynakliyat',
];

$static_pages = ['index', 'blog', 'iletisim', 'galeri', 'teklif-alin', 'form_display', 'popup_form'];

function extractInternalLinks($html, $site_domains)
{
    $links = [];
    if (empty($html)) return $links;

    preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
    if (empty($matches[1])) return $links;

    foreach ($matches[1] as $url) {
        $url = trim($url);
        if (empty($url) || $url === '#' || $url === '/' || strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0 || strpos($url, 'javascript:') === 0 || strpos($url, 'whatsapp:') === 0) {
            continue;
        }

        $is_internal = false;

        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $is_internal = true;
        }

        foreach ($site_domains as $domain) {
            if (strpos($url, $domain) !== false) {
                $is_internal = true;
                break;
            }
        }

        if ($is_internal) {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '/';
            $path = preg_replace('#^/(mynakliyat/)?#', '/', $path);
            $path = rtrim($path, '/');
            if (empty($path)) $path = '/';

            $slug = ltrim($path, '/');
            $slug = preg_replace('/\.php$/', '', $slug);

            $links[] = [
                'original_url' => $url,
                'slug' => $slug,
                'path' => $path,
            ];
        }
    }

    return $links;
}

function isValidSlug($conn, $slug, $static_pages)
{
    if (empty($slug) || $slug === '/' || $slug === 'index') return true;

    if (in_array($slug, $static_pages)) return true;

    if (preg_match('#^blog/(kategori|etiket)/#', $slug)) return true;

    $stmt = $conn->prepare("SELECT id FROM pages WHERE slug = ? AND status = 1 LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return true;
    $stmt->close();

    $stmt = $conn->prepare("SELECT id FROM services WHERE slug = ? AND status = 1 LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return true;
    $stmt->close();

    $stmt = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ? AND durum = 3 LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) return true;
    $stmt->close();

    $root = realpath(__DIR__ . '/../');
    if (file_exists($root . '/' . $slug . '.php')) return true;

    return false;
}

$broken_links = [];
$total_links = 0;
$scanned = false;

if (isset($_GET['scan']) || isset($_POST['action'])) {
    $scanned = true;
    $content_sources = [];

    $result = $conn->query("SELECT id, title, slug, content FROM pages WHERE status = 1");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $content_sources[] = ['type' => 'Sayfa', 'id' => $row['id'], 'title' => $row['title'], 'slug' => $row['slug'], 'content' => $row['content'], 'table' => 'pages', 'content_field' => 'content'];
        }
    }

    $result = $conn->query("SELECT id, ana_baslik as title, slug, aciklama as content FROM services WHERE status = 1");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $content_sources[] = ['type' => 'Hizmet', 'id' => $row['id'], 'title' => $row['title'], 'slug' => $row['slug'], 'content' => $row['content'], 'table' => 'services', 'content_field' => 'aciklama'];
        }
    }

    $result = $conn->query("SELECT id, baslik as title, slug, icerik as content FROM blog_posts WHERE durum = 3");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $content_sources[] = ['type' => 'Blog', 'id' => $row['id'], 'title' => $row['title'], 'slug' => $row['slug'], 'content' => $row['content'], 'table' => 'blog_posts', 'content_field' => 'icerik'];
        }
    }

    foreach ($content_sources as $source) {
        $links = extractInternalLinks($source['content'], $site_domains);
        $total_links += count($links);

        foreach ($links as $link) {
            if (!isValidSlug($conn, $link['slug'], $static_pages)) {
                $broken_links[] = [
                    'source_type' => $source['type'],
                    'source_id' => $source['id'],
                    'source_title' => $source['title'],
                    'source_slug' => $source['slug'],
                    'source_table' => $source['table'],
                    'content_field' => $source['content_field'],
                    'broken_url' => $link['original_url'],
                    'broken_slug' => $link['slug'],
                ];
            }
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'remove_link') {
    $table = $_POST['table'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $url = $_POST['url'] ?? '';

    $allowed_tables = ['pages' => 'content', 'services' => 'aciklama', 'blog_posts' => 'icerik'];
    if (isset($allowed_tables[$table]) && $id > 0 && !empty($url)) {
        $col = $allowed_tables[$table];
        $stmt = $conn->prepare("SELECT `$col` FROM `$table` WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $content = $row[$col];
            $escaped_url = preg_quote($url, '/');
            $new_content = preg_replace('/<a\s[^>]*href=["\']' . $escaped_url . '["\'][^>]*>(.*?)<\/a>/is', '$1', $content);

            if ($new_content !== $content) {
                $stmt = $conn->prepare("UPDATE `$table` SET `$col` = ? WHERE id = ?");
                $stmt->bind_param("si", $new_content, $id);
                $stmt->execute();
                $stmt->close();
                echo '<div class="alert alert-success alert-dismissible fade show"><i class="bx bx-check-circle me-2"></i><strong>Başarılı!</strong> "' . htmlspecialchars($url) . '" linki kaldırıldı. Metin korundu.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'remove_all') {
    $removed = 0;
    foreach ($broken_links as $bl) {
        $table = $bl['source_table'];
        $id = $bl['source_id'];
        $url = $bl['broken_url'];
        $allowed_tables = ['pages' => 'content', 'services' => 'aciklama', 'blog_posts' => 'icerik'];
        if (!isset($allowed_tables[$table])) continue;
        $col = $allowed_tables[$table];

        $stmt = $conn->prepare("SELECT `$col` FROM `$table` WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $content = $row[$col];
            $escaped_url = preg_quote($url, '/');
            $new_content = preg_replace('/<a\s[^>]*href=["\']' . $escaped_url . '["\'][^>]*>(.*?)<\/a>/is', '$1', $content);
            if ($new_content !== $content) {
                $stmt = $conn->prepare("UPDATE `$table` SET `$col` = ? WHERE id = ?");
                $stmt->bind_param("si", $new_content, $id);
                $stmt->execute();
                $stmt->close();
                $removed++;
            }
        }
    }
    echo '<div class="alert alert-success"><i class="bx bx-check-circle me-2"></i><strong>' . $removed . ' kırık link</strong> başarıyla kaldırıldı. Metinler korundu.</div>';
    $broken_links = [];
    $scanned = false;
}
?>

<div class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1"><i class="bx bx-link-external me-2"></i>Kırık Link Tarayıcı</h5>
                        <p class="text-muted mb-0 small">Sayfa, hizmet ve blog içeriklerindeki dahili kırık linkleri tespit edip temizler.</p>
                    </div>
                    <a href="?scan=1" class="btn btn-primary">
                        <i class="bx bx-search-alt me-1"></i> Taramayı Başlat
                    </a>
                </div>

                <?php if ($scanned): ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center py-3">
                                    <div class="fs-3 fw-bold text-primary"><?= $total_links ?></div>
                                    <small class="text-muted">Toplam Dahili Link</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center py-3">
                                    <div class="fs-3 fw-bold text-success"><?= $total_links - count($broken_links) ?></div>
                                    <small class="text-muted">Sağlam Link</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card <?= count($broken_links) > 0 ? 'bg-danger bg-opacity-10 border-danger' : 'bg-light' ?> border-0">
                                <div class="card-body text-center py-3">
                                    <div class="fs-3 fw-bold <?= count($broken_links) > 0 ? 'text-danger' : 'text-success' ?>"><?= count($broken_links) ?></div>
                                    <small class="text-muted">Kırık Link</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (count($broken_links) > 0): ?>
                        <form method="post" action="?scan=1" class="mb-3">
                            <input type="hidden" name="action" value="remove_all">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Tüm kırık linkler kaldırılacak. Link metinleri korunacak. Emin misiniz?')">
                                <i class="bx bx-trash me-1"></i> Tümünü Kaldır (<?= count($broken_links) ?>)
                            </button>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-sm" id="brokenLinksTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kaynak</th>
                                        <th>Sayfa</th>
                                        <th>Kırık Link</th>
                                        <th>Hedef Slug</th>
                                        <th width="140">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($broken_links as $bl): ?>
                                        <tr>
                                            <td><span class="badge bg-<?= $bl['source_type'] === 'Blog' ? 'info' : ($bl['source_type'] === 'Hizmet' ? 'warning' : 'primary') ?>"><?= $bl['source_type'] ?></span></td>
                                            <td>
                                                <a href="<?= SITE_URL ?>/<?= htmlspecialchars($bl['source_slug']) ?>" target="_blank" class="text-decoration-none">
                                                    <?= htmlspecialchars(mb_strimwidth($bl['source_title'], 0, 50, '...')) ?>
                                                </a>
                                            </td>
                                            <td><code class="text-danger small"><?= htmlspecialchars($bl['broken_url']) ?></code></td>
                                            <td><code class="small"><?= htmlspecialchars($bl['broken_slug']) ?></code></td>
                                            <td>
                                                <form method="post" action="?scan=1" class="d-inline">
                                                    <input type="hidden" name="action" value="remove_link">
                                                    <input type="hidden" name="table" value="<?= $bl['source_table'] ?>">
                                                    <input type="hidden" name="id" value="<?= $bl['source_id'] ?>">
                                                    <input type="hidden" name="field" value="<?= $bl['content_field'] ?>">
                                                    <input type="hidden" name="url" value="<?= htmlspecialchars($bl['broken_url']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu link kaldırılacak, metin korunacak. Emin misiniz?')">
                                                        <i class="bx bx-unlink"></i> Kaldır
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-0">
                            <i class="bx bx-check-circle me-2 fs-5"></i>
                            <strong>Harika!</strong> Hiç kırık dahili link bulunamadı.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bx bx-link-external" style="font-size: 4rem;"></i>
                        <p class="mt-3">Taramayı başlatmak için yukarıdaki butona tıklayın.</p>
                        <p class="small">Tüm sayfa, hizmet ve blog içeriklerindeki dahili linkler kontrol edilecek.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#brokenLinksTable').length && $.fn.DataTable) {
        $('#brokenLinksTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json' },
            pageLength: 25,
            order: [[0, 'asc']]
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
